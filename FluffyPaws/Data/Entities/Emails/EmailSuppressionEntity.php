<?php

namespace FluffyPaws\Data\Entities\Emails;

use Fluffy\Data\Entities\BaseEntity;

/**
 * An address we must not send to any more. One row per address.
 *
 * Written by the SES notification webhook on a permanent bounce or a spam
 * complaint (and by an admin by hand), read by EmailLogService before every
 * dispatch. This is the app-side half of the ESP contract: the provider keeps
 * its own suppression list, but a sender that keeps *offering* dead addresses
 * still burns its bounce/complaint rate, and those rates are what get sending
 * paused. Cheaper to never hand the address over.
 *
 * Email is stored lower-cased (normalised by EmailSuppressionService) and has a
 * unique index — the webhook is a redelivery-prone path, so the write is an
 * upsert around that constraint.
 */
class EmailSuppressionEntity extends BaseEntity
{
    /** Lower-cased recipient address. Unique. */
    public string $Email;
    /** EmailSuppressionReason::Bounce | Complaint | Manual. */
    public string $Reason;
    /** Where it came from: EmailSuppressionSource::Ses | Admin. */
    public string $Source;
    /** Provider detail, e.g. 'Permanent/General' or the complaint feedback type. */
    public ?string $Detail = null;
    /** Micro-timestamp of the most recent event that (re)affirmed the suppression. */
    public ?int $LastEventOn = null;
}
