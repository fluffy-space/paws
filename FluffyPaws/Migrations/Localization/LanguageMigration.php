<?php

namespace FluffyPaws\Migrations\Localization;

use FluffyPaws\Data\Entities\Localization\LanguageEntity;
use FluffyPaws\Data\Entities\Localization\LanguageEntityMap;
use Fluffy\Data\Entities\CommonMap;
use Fluffy\Data\Repositories\MigrationRepository;
use FluffyPaws\Data\Repositories\LanguageRepository;
use Fluffy\Migrations\BaseMigration;

class LanguageMigration extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private LanguageRepository $languageRepository)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        $this->languageRepository->createTable(
            [
                'Id' => CommonMap::$Id,
                'Name' => CommonMap::$TextCaseInsensitive,
                'LanguageCulture' => CommonMap::$VarChar255,
                'SeoCode' => CommonMap::$VarChar255,
                'Rtl' => CommonMap::$Boolean,
                'Published' => CommonMap::$Boolean,
                'DisplayOrder' => CommonMap::$Int,

                'CreatedOn' => CommonMap::$MicroDateTime,
                'CreatedBy' => CommonMap::$VarChar255Null,
                'UpdatedOn' => CommonMap::$MicroDateTime,
                'UpdatedBy' => CommonMap::$VarChar255Null,
            ],
            ['Id'],
            [
                'UX_SeoCode' => [
                    'Columns' => [LanguageEntityMap::PROPERTY_SeoCode],
                    'Unique' => true
                ]
            ],
            []
        );

        $englishUS = new LanguageEntity();
        $englishUS->LanguageCulture = "en-US";
        $englishUS->Name = "English";
        $englishUS->Published = true;
        $englishUS->SeoCode = "en";
        $this->languageRepository->create($englishUS);
    }

    public function down()
    {
        $this->languageRepository->dropTable(true, true);
    }
}
