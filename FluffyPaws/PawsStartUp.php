<?php

namespace FluffyPaws;

use Fluffy\Domain\App\BaseApp;
use DotDi\DependencyInjection\IServiceProvider;
use DotDi\DependencyInjection\ServiceProviderHelper;
use Fluffy\Domain\App\IStartUp;
use Fluffy\Migrations\IMigrationsContext;
use FluffyPaws\Controllers\ControllersMark;
use FluffyPaws\Data\Repositories\BlogPostRepository;
use FluffyPaws\Data\Repositories\LanguageRepository;
use FluffyPaws\Data\Repositories\LocaleResourceRepository;
use FluffyPaws\Data\Repositories\MenuItemRepository;
use FluffyPaws\Data\Repositories\PageRepository;
use FluffyPaws\Data\Repositories\PictureRepository;
use FluffyPaws\Migrations\MigrationsContext;
use FluffyPaws\Migrations\MigrationsMark;
use FluffyPaws\Services\Emails\EmailConnector;
use FluffyPaws\Services\Emails\EmailService;
use FluffyPaws\Services\Localization\LocalizationService;
use FluffyPaws\Services\Sitemap\SitemapService;
use Pupils\FluffyPupils;

/** @namespaces **/
// !Do not delete the line above!

class PawsStartUp implements IStartUp
{

    public function configureServices(IServiceProvider $serviceProvider): void
    {
        $serviceProvider->addScoped(BlogPostRepository::class);
        $serviceProvider->addScoped(PageRepository::class);
        $serviceProvider->addScoped(LanguageRepository::class);
        $serviceProvider->addScoped(LocaleResourceRepository::class);
        $serviceProvider->addScoped(PictureRepository::class);
        $serviceProvider->addScoped(MenuItemRepository::class);
        $serviceProvider->addScoped(SitemapService::class);
        $serviceProvider->addScoped(EmailService::class);
        $serviceProvider->addScoped(LocalizationService::class);
        $serviceProvider->addSingleton(EmailConnector::class);
        /** @insert **/
        // !Do not delete the line above!

        // Controllers
        ServiceProviderHelper::discover($serviceProvider, [ControllersMark::folder()]);
    }

    public function configureDb(IServiceProvider $serviceProvider): void
    {
        DbContextSetUp::configure();
    }

    public function configure(BaseApp $app)
    {
        // prepare routes and Viewi        
        $serviceProvider = $app->getProvider();
        $viewiApp = $serviceProvider->get(\Viewi\App::class);
        $viewiConfig = $viewiApp->getConfig();
        $viewiConfig->use(FluffyPupils::class);
        $viewiConfig->noJsNamespace[] = 'Pupils\\Components\\Emails\\';
        include_once __DIR__ . '/routes.php';
    }

    public function configureMigrations(IServiceProvider $serviceProvider): void
    {
        ServiceProviderHelper::discover($serviceProvider, [MigrationsMark::folder()]);
        $serviceProvider->addScoped(IMigrationsContext::class, MigrationsContext::class);
    }

    public function configureInstallDependencies(IServiceProvider $serviceProvider): void {}

    public function buildDependencies(IServiceProvider $serviceProvider) {}
}
