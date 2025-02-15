<?php

namespace FluffyPaws\Migrations\Blog;

use Fluffy\Data\Entities\CommonMap;
use Fluffy\Data\Repositories\MigrationRepository;
use FluffyPaws\Data\Repositories\BlogPostRepository;
use Fluffy\Migrations\BaseMigration;

class BlogPostMigration extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private BlogPostRepository $blogPostRepository)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        $this->blogPostRepository->createTable(
            [
                'Id' => CommonMap::$Id,
                'IncludeInSitemap' => CommonMap::$Boolean,
                'Slug' => CommonMap::$VarChar400,
                'Title' => CommonMap::$TextCaseInsensitive,
                'AsHtml' => CommonMap::$Boolean,
                'Body' => CommonMap::$TextCaseInsensitive,
                'BodyOverview' => CommonMap::$TextCaseInsensitive,
                'Author' => CommonMap::$BigIntNull,
                'PictureId' => CommonMap::$BigIntNull,
                'Published' => CommonMap::$Boolean,
                'AllowComments' => CommonMap::$Boolean,
                'Tags' => CommonMap::$TextCaseInsensitiveNull,
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
                    'Columns' => ['Slug'],
                    'Unique' => true
                ]
            ],
            []
        );
    }

    public function down()
    {
        $this->blogPostRepository->dropTable(true, true);
    }
}
