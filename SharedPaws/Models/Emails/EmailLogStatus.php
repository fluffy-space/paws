<?php

namespace SharedPaws\Models\Emails;

/**
 * Lifecycle status values stored on EmailLogEntity.Status. Kept in SharedPaws so
 * both the EmailLogService (server) and any client-side rendering use one source.
 */
final class EmailLogStatus
{
    /** Row created, SMTP send not yet resolved. */
    public const Sending = 'sending';
    /** EmailConnector reported success. */
    public const Sent = 'sent';
    /** EmailConnector reported a transport error (see Error). */
    public const Failed = 'failed';
}
