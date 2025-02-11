<?php

namespace FluffyPaws\Migrations;

use Fluffy\Migrations\BaseMigrationsContext;
use FluffyPaws\Migrations\Blog\BlogPostMigration;
use FluffyPaws\Migrations\Content\PageMigration;
use FluffyPaws\Migrations\Localization\LanguageMigration;
use FluffyPaws\Migrations\Localization\LocaleEnglishMigration;
use FluffyPaws\Migrations\Localization\LocaleEnglishMigration02;
use FluffyPaws\Migrations\Localization\LocaleResourceMigration;
use FluffyPaws\Migrations\Media\PictureMigration;

/** @namespaces **/
// !Do not delete the line above!

class MigrationsContext extends BaseMigrationsContext
{
    public function run()
    {
        $this->runMigration(BlogPostMigration::class);
        $this->runMigration(PageMigration::class);
        $this->runMigration(LanguageMigration::class);
        $this->runMigration(LocaleResourceMigration::class);
        $this->runMigration(LocaleEnglishMigration::class);
        $this->runMigration(PictureMigration::class);
        $this->runMigration(LocaleEnglishMigration02::class);
        /** @insert **/
        // !Do not delete the line above!
    }
}
