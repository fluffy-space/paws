<?php

namespace FluffyPaws\Services\Emails;

use Fluffy\Services\Settings\SettingsService;
use SharedPaws\Models\Emails\EmailSuppressionReason;
use SharedPaws\Models\Emails\EmailSuppressionSource;
use Swoole\Coroutine\Http\Client;
use Throwable;

/**
 * Receives Amazon SES bounce/complaint notifications delivered over SNS and
 * turns them into suppression-list entries.
 *
 * Why this exists: SES pauses or revokes sending on a bounce rate over ~5% or a
 * complaint rate over ~0.1%. Those are *rates over what we send*, so the only
 * real control is to stop sending to addresses already known bad — which means
 * we have to be told, which means this endpoint.
 *
 * Trust chain, in order, all of it mandatory:
 *   1. the SNS signature proves the message came from AWS SNS (SnsMessageVerifier);
 *   2. the TopicArn allowlist proves it came from *our* topic — without step 2,
 *      step 1 only proves *somebody's* AWS account sent it;
 *   3. only then may an address be added to the suppression list.
 *
 * Synchronous by design: a couple of indexed writes per event, and SNS retries
 * on a non-2xx, so there is nothing to gain from the task pool here.
 */
class SesNotificationService
{
    public function __construct(
        private SettingsService $settings,
        private SnsMessageVerifier $verifier,
        private EmailSuppressionService $suppressions,
    ) {}

    /**
     * @return string 'ok' | 'confirmed' | 'ignored' | 'bad_request' | 'bad_signature' | 'forbidden_topic'
     */
    public function receive(string $rawBody, string $messageTypeHeader = ''): string
    {
        $message = json_decode($rawBody, true);
        if (!is_array($message)) {
            return 'bad_request';
        }
        $type = (string) ($message['Type'] ?? $messageTypeHeader);
        if ($type === '') {
            return 'bad_request';
        }

        if ($this->settings->getBool(MailSettings::SNS_VERIFY_SIGNATURE, true)
            && !$this->verifier->verify($message)
        ) {
            echo '[SES] rejected a notification with an invalid SNS signature.' . PHP_EOL;
            return 'bad_signature';
        }

        $topicArn = (string) ($message['TopicArn'] ?? '');
        if (!$this->isAllowedTopic($topicArn)) {
            echo '[SES] ' . $type . ' from an unlisted topic was ignored: ' . $topicArn
                . ' — add it to the "' . MailSettings::SNS_TOPIC_ARNS . '" setting to accept it.' . PHP_EOL;
            return 'forbidden_topic';
        }

        return match ($type) {
            'SubscriptionConfirmation' => $this->confirmSubscription($message),
            'Notification' => $this->handleNotification($message),
            'UnsubscribeConfirmation' => $this->logUnsubscribe($topicArn),
            default => 'ignored',
        };
    }

    /** Topic allowlist. Empty setting = closed, deliberately — see MailSettings. */
    private function isAllowedTopic(string $topicArn): bool
    {
        if ($topicArn === '') {
            return false;
        }
        $raw = $this->settings->getString(MailSettings::SNS_TOPIC_ARNS, '');
        $allowed = preg_split('/[\s,]+/', trim($raw)) ?: [];
        foreach ($allowed as $arn) {
            if ($arn !== '' && strcasecmp(trim($arn), $topicArn) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Complete the subscription handshake by fetching SubscribeURL. The URL is
     * AWS-supplied, but it arrived over the wire, so its host is re-checked —
     * a signed message from an allowlisted topic is still not a licence to make
     * an arbitrary outbound GET.
     */
    private function confirmSubscription(array $message): string
    {
        $url = (string) ($message['SubscribeURL'] ?? '');
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        if (($parts['scheme'] ?? '') !== 'https'
            || preg_match('/^sns\.[a-z0-9\-]+\.amazonaws\.com(\.cn)?$/', $host) !== 1
        ) {
            echo '[SES] refused to confirm a subscription with a non-SNS SubscribeURL: ' . $url . PHP_EOL;
            return 'bad_request';
        }

        $path = ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        try {
            $client = new Client($host, 443, true);
            $client->set(['timeout' => 10]);
            $client->get($path);
            $status = $client->statusCode;
            $client->close();
            echo '[SES] subscription confirmation for ' . ($message['TopicArn'] ?? '?')
                . ' returned HTTP ' . $status . PHP_EOL;
            return $status >= 200 && $status < 300 ? 'confirmed' : 'bad_request';
        } catch (Throwable $t) {
            echo '[SES] subscription confirmation failed: ' . $t->getMessage() . PHP_EOL;
            return 'bad_request';
        }
    }

    private function logUnsubscribe(string $topicArn): string
    {
        echo '[SES] our endpoint was unsubscribed from ' . $topicArn
            . ' — bounces and complaints are no longer reaching the suppression list.' . PHP_EOL;
        return 'ok';
    }

    /** The SNS envelope's Message payload is the SES event, as a JSON string. */
    private function handleNotification(array $message): string
    {
        $payload = json_decode((string) ($message['Message'] ?? ''), true);
        if (!is_array($payload)) {
            return 'bad_request';
        }

        // Two shapes carry the same events: a topic wired directly to SES
        // (notificationType) and a configuration-set event destination (eventType).
        $eventType = (string) ($payload['notificationType'] ?? ($payload['eventType'] ?? ''));

        return match ($eventType) {
            'Bounce' => $this->handleBounce($payload),
            'Complaint' => $this->handleComplaint($payload),
            // Delivery / Send / Open / Click / Reject / DeliveryDelay: nothing to
            // suppress. Reject is SES refusing our message (e.g. virus), not the
            // recipient being bad, so it must not remove a recipient.
            default => 'ignored',
        };
    }

    /**
     * Permanent bounces only. A Transient bounce is a full mailbox or a greylist —
     * suppressing on those would delete real customers' mail over a temporary
     * condition, and SES already counts them separately.
     */
    private function handleBounce(array $payload): string
    {
        $bounce = $payload['bounce'] ?? [];
        $bounceType = (string) ($bounce['bounceType'] ?? '');
        $subType = (string) ($bounce['bounceSubType'] ?? '');
        if ($bounceType !== 'Permanent') {
            echo '[SES] ' . ($bounceType ?: 'unknown') . ' bounce (' . $subType . ') — not suppressed.' . PHP_EOL;
            return 'ok';
        }

        $detail = trim($bounceType . '/' . $subType, '/');
        foreach ($this->addressesOf($bounce['bouncedRecipients'] ?? []) as $address) {
            $this->suppressions->suppress(
                $address,
                EmailSuppressionReason::Bounce,
                EmailSuppressionSource::Ses,
                $detail
            );
            echo '[SES] suppressed ' . $address . ' (permanent bounce: ' . $detail . ')' . PHP_EOL;
        }
        return 'ok';
    }

    /** Every complaint suppresses — this is the rate that gets sending revoked. */
    private function handleComplaint(array $payload): string
    {
        $complaint = $payload['complaint'] ?? [];
        $detail = (string) ($complaint['complaintFeedbackType'] ?? 'complaint');
        foreach ($this->addressesOf($complaint['complainedRecipients'] ?? []) as $address) {
            $this->suppressions->suppress(
                $address,
                EmailSuppressionReason::Complaint,
                EmailSuppressionSource::Ses,
                $detail
            );
            echo '[SES] suppressed ' . $address . ' (complaint: ' . $detail . ')' . PHP_EOL;
        }
        return 'ok';
    }

    /**
     * SES gives recipients as objects; the address may also carry a display name
     * ("Name <a@b.c>"), which is not what we key the suppression list on.
     *
     * @return string[]
     */
    private function addressesOf(mixed $recipients): array
    {
        if (!is_array($recipients)) {
            return [];
        }
        $addresses = [];
        foreach ($recipients as $recipient) {
            $address = is_array($recipient) ? ($recipient['emailAddress'] ?? null) : $recipient;
            if (!is_string($address) || $address === '') {
                continue;
            }
            if (preg_match('/<([^>]+)>/', $address, $matches) === 1) {
                $address = $matches[1];
            }
            $addresses[] = trim($address);
        }
        return $addresses;
    }
}
