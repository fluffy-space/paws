<?php

namespace FluffyPaws\Services\Emails;

use Fluffy\Data\Repositories\BasePostgresqlRepository;
use FluffyPaws\Data\Entities\Emails\EmailSuppressionEntity;
use FluffyPaws\Data\Repositories\EmailSuppressionRepository;
use SharedPaws\Models\Emails\EmailSuppressionReason;
use SharedPaws\Models\Emails\EmailSuppressionSource;
use Throwable;

/**
 * The suppression list: addresses we refuse to hand to the mail transport.
 *
 * Two writers — the SES notification webhook (permanent bounce / spam complaint)
 * and an admin by hand — and one reader, EmailLogService, which consults it
 * before every dispatch.
 *
 * Deliberately *not* cached. Mail volume is transactional-only (tens per day),
 * so one indexed lookup per send is free, while a stale cache here means either
 * sending to an address a provider already told us to stop mailing (the
 * expensive mistake) or silently swallowing mail to an address just released.
 *
 * Scoped, like every repository consumer.
 */
class EmailSuppressionService
{
    public function __construct(private EmailSuppressionRepository $repository) {}

    /** Lower-case + trim. The single normalisation rule for stored and queried addresses. */
    public static function normalize(string $email): string
    {
        return strtolower(trim($email));
    }

    /**
     * Is this address suppressed? Fails OPEN — a database problem must not
     * silently stop password resets going out. The provider's own suppression
     * list is the backstop for that window.
     */
    public function isSuppressed(string $email): bool
    {
        return $this->find($email) !== null;
    }

    /** The suppression row for an address, or null. */
    public function find(string $email): ?EmailSuppressionEntity
    {
        $normalized = self::normalize($email);
        if ($normalized === '') {
            return null;
        }
        try {
            return $this->repository->findByEmail($normalized);
        } catch (Throwable $t) {
            echo '[EmailSuppression] lookup failed for ' . $normalized . ': ' . $t->getMessage() . PHP_EOL;
            return null;
        }
    }

    /**
     * Add (or refresh) a suppression. Idempotent: a redelivered webhook event
     * updates the existing row instead of racing the unique index.
     *
     * A complaint outranks a bounce — if an address bounced and later complained,
     * the row keeps the complaint, because that is the reason that matters when
     * someone asks why we stopped sending.
     *
     * @param string $reason EmailSuppressionReason::*
     * @param string $source EmailSuppressionSource::*
     */
    public function suppress(
        string $email,
        string $reason,
        string $source = EmailSuppressionSource::Ses,
        ?string $detail = null
    ): bool {
        $normalized = self::normalize($email);
        if ($normalized === '' || strpos($normalized, '@') === false) {
            return false;
        }

        $existing = $this->find($normalized);
        if ($existing !== null) {
            if ($existing->Reason !== EmailSuppressionReason::Complaint) {
                $existing->Reason = $reason;
                $existing->Detail = $detail;
            }
            $existing->LastEventOn = BasePostgresqlRepository::getTime();
            try {
                $this->repository->update($existing);
                return true;
            } catch (Throwable $t) {
                echo '[EmailSuppression] refresh failed for ' . $normalized . ': ' . $t->getMessage() . PHP_EOL;
                return false;
            }
        }

        $entity = new EmailSuppressionEntity();
        $entity->Email = $normalized;
        $entity->Reason = $reason;
        $entity->Source = $source;
        $entity->Detail = $detail;
        $entity->LastEventOn = BasePostgresqlRepository::getTime();
        try {
            return $this->repository->create($entity);
        } catch (Throwable $t) {
            // Lost the race on UX_EmailSuppression_Email — the row exists, which
            // is the outcome we wanted.
            if ($this->find($normalized) !== null) {
                return true;
            }
            echo '[EmailSuppression] insert failed for ' . $normalized . ': ' . $t->getMessage() . PHP_EOL;
            return false;
        }
    }

    /** Take an address off the list (mailbox fixed, complaint resolved). */
    public function release(string $email): bool
    {
        $existing = $this->find($email);
        if ($existing === null) {
            return false;
        }
        return (bool) $this->repository->delete($existing);
    }
}
