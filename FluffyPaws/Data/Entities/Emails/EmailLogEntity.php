<?php

namespace FluffyPaws\Data\Entities\Emails;

use Fluffy\Data\Entities\BaseEntity;

/**
 * One outgoing email send attempt. Written around every dispatch via
 * EmailLogService so a failed transactional email is no longer invisible
 * (previously failures were only echo'd to stdout in EmailConnector::send).
 *
 * Body is intentionally nullable and populated only when email.store_body is on
 * (dev/local) — in prod we keep metadata only, never the rendered HTML (PII/bloat).
 * Old rows are pruned by EmailLogCleanupTask.
 */
class EmailLogEntity extends BaseEntity
{
    /** Recipient email address. */
    public string $Recipient;
    /** Recipient display name, if any. */
    public ?string $RecipientName = null;
    /** Logical email kind, e.g. 'confirm-email', 'reset-password', 'team-invitation'. */
    public string $Type;
    public string $Subject;
    /** EmailLogStatus::Sending | Sent | Failed. */
    public string $Status;
    /** SMTP/transport error message when Status is Failed. */
    public ?string $Error = null;
    /** Rendered HTML body — only stored when email.store_body is enabled (dev/local). */
    public ?string $Body = null;
    /** Micro-timestamp the send completed (success or failure); null while Sending. */
    public ?int $SentOn = null;
}
