<?php

namespace FluffyPaws\Services\Emails;

use Fluffy\Domain\Configuration\Config;
use FluffyPaws\Data\Entities\Emails\EmailLogEntity;
use FluffyPaws\Data\Repositories\EmailLogRepository;
use SharedPaws\Models\Emails\EmailLogStatus;
use Throwable;

/**
 * Single chokepoint for outgoing mail: persists an EmailLog row, delegates the
 * actual transport to EmailConnector, then records the outcome. Senders
 * (EmailService, app-level mailers) call this instead of EmailConnector directly
 * so a failed/never-attempted transactional email is recorded rather than only
 * echo'd to stdout.
 *
 * Scoped (not singleton) because it owns a scoped EmailLogRepository; this runs
 * inside the Swoole task scope where mail is dispatched, so DB access is valid.
 *
 * The rendered HTML body is stored only when email.store_body is enabled — true
 * in dev/local (where SMTP is usually unconfigured and nothing is delivered, so
 * the admin preview is the only way to see the email), false in prod (no PII/bloat).
 */
class EmailLogService
{
    public function __construct(
        private EmailConnector $connector,
        private EmailLogRepository $repository,
        private Config $config,
    ) {
    }

    /**
     * @param string $type logical email kind, e.g. 'confirm-email', 'reset-password', 'team-invitation'
     * @param null|EmailAttachment[] $attachments
     * @return array EmailConnector::send result (['success' => bool, 'message' => ?string])
     */
    public function send(string $type, string $emailTo, string $subject, string $body, string $emailName = '', string $altBody = '', ?array $attachments = null): array
    {
        $storeBody = $this->config->values['email']['store_body'] ?? false;

        $entity = new EmailLogEntity();
        $entity->Recipient = $emailTo;
        $entity->RecipientName = $emailName !== '' ? $emailName : null;
        $entity->Type = $type;
        $entity->Subject = $subject;
        $entity->Status = EmailLogStatus::Sending;
        $entity->Body = $storeBody ? $body : null;
        $entity->Error = null;
        $entity->SentOn = null;

        // Record the attempt up front so a crash mid-send still leaves a 'sending' row.
        $logged = $this->tryCreate($entity);

        $result = $this->connector->send($emailTo, $subject, $body, $emailName, $altBody, $attachments);

        if (($result['success'] ?? false) === true) {
            $entity->Status = EmailLogStatus::Sent;
            $entity->Error = null;
        } else {
            $entity->Status = EmailLogStatus::Failed;
            $entity->Error = $result['message'] ?? 'Unknown error';
        }
        $entity->SentOn = EmailLogRepository::getTime();

        if ($logged) {
            $this->tryUpdate($entity);
        }

        return $result;
    }

    /** Logging must never break delivery — swallow + report storage errors. */
    private function tryCreate(EmailLogEntity $entity): bool
    {
        try {
            return $this->repository->create($entity);
        } catch (Throwable $t) {
            echo '[EmailLog] failed to record send attempt.' . PHP_EOL;
            echo $t->__toString() . PHP_EOL;
            return false;
        }
    }

    private function tryUpdate(EmailLogEntity $entity): void
    {
        try {
            $this->repository->update($entity);
        } catch (Throwable $t) {
            echo '[EmailLog] failed to record send outcome.' . PHP_EOL;
            echo $t->__toString() . PHP_EOL;
        }
    }
}
