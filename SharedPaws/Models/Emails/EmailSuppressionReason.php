<?php

namespace SharedPaws\Models\Emails;

/**
 * Why an address is on the suppression list, and who put it there. Kept in
 * SharedPaws so the webhook (server) and the admin UI use one vocabulary.
 */
final class EmailSuppressionReason
{
    /** Provider reported a PERMANENT bounce — the mailbox does not exist. */
    public const Bounce = 'bounce';
    /** Recipient hit "this is spam". The one that actually costs us reputation. */
    public const Complaint = 'complaint';
    /** Added by hand from the admin area. */
    public const Manual = 'manual';
}
