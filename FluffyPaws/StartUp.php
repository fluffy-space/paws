<?php

namespace FluffyPaws;

use Fluffy\Domain\App\BaseApp;
use DotDi\DependencyInjection\IServiceProvider;
use DotDi\DependencyInjection\ServiceProviderHelper;
use Fluffy\Domain\App\IStartUp;
use FluffyPaws\Controllers\ControllersMark;
use FluffyPaws\Data\Repositories\BlogPostRepository;
use FluffyPaws\Data\Repositories\PictureRepository;
use FluffyPaws\Migrations\MigrationsMark;
use FluffyPaws\Services\Sitemap\SitemapService;

/** @namespaces **/
// !Do not delete the line above!

class StartUp implements IStartUp
{
    public function configure(BaseApp $app) {}

    public function configureServices(IServiceProvider $serviceProvider): void
    {
        $serviceProvider->addScoped(BlogPostRepository::class);
        $serviceProvider->addScoped(PictureRepository::class);
        $serviceProvider->addScoped(SitemapService::class);
        /** @insert **/
        // !Do not delete the line above!

        // Controllers
        ServiceProviderHelper::discover($serviceProvider, [ControllersMark::folder()]);
    }

    public function configureDb(IServiceProvider $serviceProvider): void
    {
        DbContextSetUp::configure();
    }

    public function configureMigrations(IServiceProvider $serviceProvider): void
    {
        ServiceProviderHelper::discover($serviceProvider, [MigrationsMark::folder()]);
    }

    public function configureInstallDependencies(IServiceProvider $serviceProvider): void {}

    public function buildDependencies(IServiceProvider $serviceProvider) {}
}
