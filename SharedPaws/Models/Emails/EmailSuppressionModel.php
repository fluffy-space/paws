<?php

namespace SharedPaws\Models\Emails;

use SharedPaws\Models\BaseModel;

/**
 * View model for the suppression list in the admin email area.
 * Mirrors EmailSuppressionEntity.
 */
class EmailSuppressionModel extends BaseModel
{
    public string $Email = '';
    public string $Reason = '';
    public string $Source = '';
    public ?string $Detail = null;
    public ?int $LastEventOn = null;
}
