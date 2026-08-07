<?php

namespace FluffyPaws\Migrations\Emails;

use Fluffy\Data\Entities\CommonMap;
use Fluffy\Data\Repositories\MigrationRepository;
use Fluffy\Migrations\BaseMigration;
use FluffyPaws\Data\Repositories\EmailSuppressionRepository;

/**
 * Creates the `EmailSuppression` table — addresses we stop sending to after a
 * permanent bounce or a spam complaint. Columns/PK/index spelled out inline: a
 * migration is an immutable schema snapshot, never derived from the live map.
 */
class EmailSuppressionMigration extends BaseMigration
{
    function __construct(
        MigrationRepository $MigrationHistoryRepository,
        private EmailSuppressionRepository $suppressions
    ) {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        $this->suppressions->createTable(
            [
                'Id' => CommonMap::$Id,
                'Email' => CommonMap::$VarChar255,
                'Reason' => CommonMap::$VarChar255,
                'Source' => CommonMap::$VarChar255,
                'Detail' => CommonMap::$TextNull,
                'LastEventOn' => CommonMap::$MicroDateTimeNull,

                'CreatedOn' => CommonMap::$MicroDateTime,
                'CreatedBy' => CommonMap::$VarChar255Null,
                'UpdatedOn' => CommonMap::$MicroDateTime,
                'UpdatedBy' => CommonMap::$VarChar255Null,
            ],
            ['Id'],
            [
                // Unique: one row per address, and the webhook leans on the
                // constraint to make redelivered events idempotent.
                'UX_EmailSuppression_Email' => ['Columns' => ['Email'], 'Unique' => true],
            ],
            []
        );
    }

    public function down()
    {
        $this->suppressions->dropTable(true, true);
    }
}
