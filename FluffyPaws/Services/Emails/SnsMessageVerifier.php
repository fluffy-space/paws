<?php

namespace FluffyPaws\Services\Emails;

use Swoole\Coroutine\Http\Client;
use Throwable;

/**
 * Verifies the cryptographic signature on an Amazon SNS message.
 *
 * This is the only thing standing between the SES webhook and the open
 * internet: the endpoint has to be public (SNS will not authenticate), so
 * "is this really from AWS" is answered here, and nowhere else. An unverified
 * endpoint is a remote "suppress any address you like" button — i.e. a way to
 * stop a chosen account receiving its password resets.
 *
 * Signature scheme (AWS docs, "Verifying the signatures of Amazon SNS messages"):
 * canonical string = a fixed, type-dependent subset of fields in a fixed order,
 * each as "Key\nValue\n"; signed with the RSA key of the certificate at
 * SigningCertURL; SignatureVersion 1 = SHA1, 2 = SHA256.
 *
 * Singleton: the per-worker certificate cache is the point.
 */
class SnsMessageVerifier
{
    /** Fields signed for a plain notification, in the order AWS signs them. */
    private const NOTIFICATION_FIELDS = ['Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type'];

    /** Fields signed for SubscriptionConfirmation / UnsubscribeConfirmation. */
    private const CONFIRMATION_FIELDS = ['Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type'];

    /** url => PEM. SNS rotates certificates rarely; a worker-lifetime cache is plenty. */
    private array $certificates = [];

    /**
     * @param array $message decoded SNS envelope
     */
    public function verify(array $message): bool
    {
        $signature = $message['Signature'] ?? null;
        $certUrl = $message['SigningCertURL'] ?? ($message['SigningCertUrl'] ?? null);
        if (!is_string($signature) || $signature === '' || !is_string($certUrl)) {
            return false;
        }
        if (!self::isAwsCertUrl($certUrl)) {
            return false;
        }

        $canonical = self::canonicalString($message);
        if ($canonical === null) {
            return false;
        }

        $pem = $this->fetchCertificate($certUrl);
        if ($pem === null) {
            return false;
        }
        $publicKey = openssl_pkey_get_public($pem);
        if ($publicKey === false) {
            return false;
        }

        $algorithm = ((string) ($message['SignatureVersion'] ?? '1')) === '2'
            ? OPENSSL_ALGO_SHA256
            : OPENSSL_ALGO_SHA1;
        $decoded = base64_decode($signature, true);
        if ($decoded === false) {
            return false;
        }

        return openssl_verify($canonical, $decoded, $publicKey, $algorithm) === 1;
    }

    /**
     * The certificate must live on an SNS host in amazonaws.com over TLS. Without
     * this check the signature proves nothing — an attacker would simply point
     * SigningCertURL at a certificate of their own.
     */
    public static function isAwsCertUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts)) {
            return false;
        }
        $scheme = strtolower($parts['scheme'] ?? '');
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';

        return $scheme === 'https'
            && preg_match('/^sns\.[a-z0-9\-]+\.amazonaws\.com(\.cn)?$/', $host) === 1
            && str_ends_with($path, '.pem');
    }

    /** "Key\nValue\n" over the signed fields, skipping ones the message doesn't carry. */
    private static function canonicalString(array $message): ?string
    {
        $type = $message['Type'] ?? '';
        $fields = match ($type) {
            'Notification' => self::NOTIFICATION_FIELDS,
            'SubscriptionConfirmation', 'UnsubscribeConfirmation' => self::CONFIRMATION_FIELDS,
            default => null,
        };
        if ($fields === null) {
            return null;
        }

        $canonical = '';
        foreach ($fields as $field) {
            // Subject is optional; every other field is mandatory for its type.
            if (!isset($message[$field])) {
                if ($field === 'Subject') {
                    continue;
                }
                return null;
            }
            $canonical .= $field . "\n" . $message[$field] . "\n";
        }
        return $canonical;
    }

    /** GET the signing certificate (coroutine client — parks, never blocks the worker). */
    private function fetchCertificate(string $url): ?string
    {
        if (isset($this->certificates[$url])) {
            return $this->certificates[$url];
        }
        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $path = ($parts['path'] ?? '') . (isset($parts['query']) ? '?' . $parts['query'] : '');
        if ($host === '' || $path === '') {
            return null;
        }

        try {
            $client = new Client($host, 443, true);
            $client->set(['timeout' => 10]);
            $client->get($path);
            $status = $client->statusCode;
            $body = $client->body;
            $client->close();

            if ($status !== 200 || !is_string($body) || strpos($body, 'BEGIN CERTIFICATE') === false) {
                return null;
            }
            $this->certificates[$url] = $body;
            return $body;
        } catch (Throwable $t) {
            echo '[SNS] certificate fetch failed (' . $url . '): ' . $t->getMessage() . PHP_EOL;
            return null;
        }
    }
}
