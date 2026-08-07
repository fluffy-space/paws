<?php

namespace SharedPaws\Models\Emails;

/** Which path wrote an EmailSuppression row. */
final class EmailSuppressionSource
{
    /** The SES/SNS notification webhook. */
    public const Ses = 'ses';
    /** An admin, through the admin area. */
    public const Admin = 'admin';
}
