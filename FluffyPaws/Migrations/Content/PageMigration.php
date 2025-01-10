<?php

namespace FluffyPaws\Migrations\Content;

use FluffyPaws\Data\Entities\Content\PageEntityMap;
use Fluffy\Data\Entities\CommonMap;
use Fluffy\Data\Repositories\MigrationRepository;
use FluffyPaws\Data\Repositories\PageRepository;
use Fluffy\Migrations\BaseMigration;

class PageMigration extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private PageRepository $pageRepository)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        $this->pageRepository->createTable(
            [
                'Id' => CommonMap::$Id,
                'IncludeInSitemap' => CommonMap::$Boolean,
                'Slug' => CommonMap::$VarChar400,
                'Title' => CommonMap::$TextCaseInsensitive,
                'AsHtml' => CommonMap::$Boolean,
                'Body' => CommonMap::$TextCaseInsensitive,
                'PictureId' => CommonMap::$BigIntNull,
                'Published' => CommonMap::$Boolean,
                'MetaKeywords' => CommonMap::$VarChar400Null,
                'MetaTitle' => CommonMap::$VarChar400Null,
                'MetaDescription' => CommonMap::$VarChar400Null,

                'CreatedOn' => CommonMap::$MicroDateTime,
                'CreatedBy' => CommonMap::$VarChar255Null,
                'UpdatedOn' => CommonMap::$MicroDateTime,
                'UpdatedBy' => CommonMap::$VarChar255Null,
            ],
            ['Id'],
            [
                'UX_Slug' => [
                    'Columns' => [PageEntityMap::PROPERTY_Slug],
                    'Unique' => true
                ]
            ],
            []
        );
    }

    public function down()
    {
        $this->pageRepository->dropTable(true, true);
    }
}
