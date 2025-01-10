<?php

namespace FluffyPaws\Migrations\Localization;

use FluffyPaws\Data\Entities\Localization\LanguageEntityMap;
use Fluffy\Data\Entities\CommonMap;
use Fluffy\Data\Repositories\MigrationRepository;
use FluffyPaws\Data\Repositories\LocaleResourceRepository;
use Fluffy\Migrations\BaseMigration;

class LocaleResourceMigration extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private LocaleResourceRepository $localeResourceRepository)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        $this->localeResourceRepository->createTable(
            [
                'Id' => CommonMap::$Id,
                'LanguageId' => CommonMap::$BigInt,
                'Name' => CommonMap::$TextCaseInsensitive,
                'Value' => CommonMap::$TextCaseInsensitive,

                'CreatedOn' => CommonMap::$MicroDateTime,
                'CreatedBy' => CommonMap::$VarChar255Null,
                'UpdatedOn' => CommonMap::$MicroDateTime,
                'UpdatedBy' => CommonMap::$VarChar255Null,
            ],
            ['Id'],
            [],
            [
                [
                    'Table' => LanguageEntityMap::class,
                    'Columns' => ['LanguageId'],
                    'References' => ['Id'],
                    'OnDelete' => CommonMap::$OnDeleteCascade
                ]
            ]
        );
    }

    public function down()
    {
        $this->localeResourceRepository->dropTable(true, true);
    }
}
