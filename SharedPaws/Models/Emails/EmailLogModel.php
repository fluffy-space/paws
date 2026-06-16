<?php

namespace SharedPaws\Models\Emails;

use SharedPaws\Models\BaseModel;

/**
 * View model for the /admin/email-logs admin area. Mirrors EmailLogEntity.
 * Body is only populated (server-side) in dev/local; the list endpoint omits it.
 */
class EmailLogModel extends BaseModel
{
    public string $Recipient = '';
    public ?string $RecipientName = null;
    public string $Type = '';
    public string $Subject = '';
    public string $Status = '';
    public ?string $Error = null;
    public ?string $Body = null;
    public ?int $SentOn = null;
}
