<?php

namespace FluffyPaws\Tasks;

use FluffyPaws\Data\Repositories\EmailLogRepository;

/**
 * Retention pruning for the EmailLog table. Schedule from the app's
 * Application\crontab.php, e.g.:
 *
 *   CronTab::schedule([EmailLogCleanupTask::class, 'execute'], '0 0 * * * *');
 *
 * Runs inside the Swoole task scope (DB access is valid there). Deletes every
 * log row older than RETENTION_DAYS in a single statement.
 */
class EmailLogCleanupTask
{
    /** How long email-log rows are kept before pruning. */
    public const RETENTION_DAYS = 30;

    public function __construct(private EmailLogRepository $emailLogs)
    {
    }

    public function execute()
    {
        $taskName = 'EmailLogCleanupTask';
        $cutoffMicro = (time() - self::RETENTION_DAYS * 24 * 60 * 60) * 1000000;
        $removed = $this->emailLogs->deleteOlderThan($cutoffMicro);
        $time = date('Y-m-d H:i:s', time());
        echo "[$taskName] $time pruned $removed email-log rows older than " . self::RETENTION_DAYS . " days." . PHP_EOL;
    }
}
