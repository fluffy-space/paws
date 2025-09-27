<?php

namespace FluffyPaws\Migrations\Content;

use Fluffy\Data\Repositories\MigrationRepository;
use FluffyPaws\Data\Repositories\PageRepository;
use Fluffy\Migrations\BaseMigration;
use FluffyPaws\Data\Entities\Content\PageEntity;

class PageContentsMigration01 extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private PageRepository $pageRepository)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        if (!$this->pageRepository->find('Slug', '')) {
            $homePage = new PageEntity();
            $homePage->Published = true;
            $homePage->Slug = '';
            $homePage->IncludeInSitemap = false;
            $homePage->Title = 'Welcome to Fluffy Paws';
            $homePage->Body = 'Welcome to Fluffy Paws';
            $this->pageRepository->create($homePage);
        }
    }

    public function down() {}
}
