<?php

namespace FluffyPaws\Migrations\Emails;

use Fluffy\Data\Entities\CommonMap;
use Fluffy\Data\Repositories\MigrationRepository;
use FluffyPaws\Data\Repositories\EmailLogRepository;
use Fluffy\Migrations\BaseMigration;

class EmailLogMigration extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private EmailLogRepository $emailLogRepository)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        $this->emailLogRepository->createTable(
            [
                'Id' => CommonMap::$Id,
                'Recipient' => CommonMap::$VarChar255,
                'RecipientName' => CommonMap::$VarChar255Null,
                'Type' => CommonMap::$VarChar255,
                'Subject' => CommonMap::$TextCaseInsensitive,
                'Status' => CommonMap::$VarChar255,
                'Error' => CommonMap::$TextNull,
                'Body' => CommonMap::$TextNull,
                'SentOn' => CommonMap::$MicroDateTimeNull,

                'CreatedOn' => CommonMap::$MicroDateTime,
                'CreatedBy' => CommonMap::$VarChar255Null,
                'UpdatedOn' => CommonMap::$MicroDateTime,
                'UpdatedBy' => CommonMap::$VarChar255Null,
            ],
            ['Id'],
            [
                'IX_EmailLog_CreatedOn' => [
                    'Columns' => ['CreatedOn'],
                    'Unique' => false
                ]
            ],
            []
        );
    }

    public function down()
    {
        $this->emailLogRepository->dropTable(true, true);
    }
}
