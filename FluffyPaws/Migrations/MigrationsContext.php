<?php

namespace FluffyPaws\Migrations;

use Fluffy\Migrations\BaseMigrationsContext;
use FluffyPaws\Migrations\Blog\BlogPostMigration;
use FluffyPaws\Migrations\Content\PageContentsMigration01;
use FluffyPaws\Migrations\Content\PageContentsMigration02;
use FluffyPaws\Migrations\Content\PageMigration;
use FluffyPaws\Migrations\Localization\LanguageMigration;
use FluffyPaws\Migrations\Localization\LocaleEnglishMigration;
use FluffyPaws\Migrations\Localization\LocaleEnglishMigration02;
use FluffyPaws\Migrations\Localization\LocaleEnglishMigration03;
use FluffyPaws\Migrations\Localization\LocaleEnglishMigration04;
use FluffyPaws\Migrations\Localization\LocaleResourceMigration;
use FluffyPaws\Migrations\Media\PictureMigration;
use FluffyPaws\Migrations\Menu\MenuItemMigration;
use FluffyPaws\Migrations\Menu\MenuItemsMigration01;
use FluffyPaws\Migrations\Menu\MenuItemsMigration02;

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
        $this->runMigration(MenuItemMigration::class);
        $this->runMigration(LocaleEnglishMigration03::class);
        $this->runMigration(LocaleEnglishMigration04::class);
        $this->runMigration(PageContentsMigration01::class);
        $this->runMigration(PageContentsMigration02::class);
        $this->runMigration(MenuItemsMigration01::class);
        $this->runMigration(MenuItemsMigration02::class);
        /** @insert **/
        // !Do not delete the line above!
    }
}
