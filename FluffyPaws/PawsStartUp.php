<?php

namespace FluffyPaws;

use Fluffy\Domain\App\BaseApp;
use Fluffy\Domain\CronTab\CronTab;
use DotDi\DependencyInjection\IServiceProvider;
use DotDi\DependencyInjection\ServiceProviderHelper;
use Fluffy\Domain\App\IStartUp;
use Fluffy\Migrations\IMigrationsContext;
use FluffyPaws\Controllers\ControllersMark;
use FluffyPaws\Data\Repositories\BlogPostRepository;
use FluffyPaws\Data\Repositories\LanguageRepository;
use FluffyPaws\Data\Repositories\LocaleResourceRepository;
use FluffyPaws\Data\Repositories\MenuItemRepository;
use FluffyPaws\Data\Repositories\EmailLogRepository;
use FluffyPaws\Data\Repositories\PageRepository;
use FluffyPaws\Data\Repositories\PictureRepository;
use FluffyPaws\Migrations\MigrationsContext;
use FluffyPaws\Migrations\MigrationsMark;
use FluffyPaws\Security\PawsPermissions;
use FluffyPaws\Services\Emails\EmailConnector;
use FluffyPaws\Services\Emails\EmailLogService;
use FluffyPaws\Services\Emails\EmailPreviewRegistry;
use FluffyPaws\Services\Emails\IEmailPreviewProvider;
use FluffyPaws\Services\Emails\PawsEmailPreviewProvider;
use FluffyPaws\Services\Emails\EmailService;
use FluffyPaws\Services\Localization\LocalizationService;
use FluffyPaws\Services\Sitemap\SitemapService;
use FluffyPaws\Tasks\EmailLogCleanupTask;
use Pupils\FluffyPupils;

/** @namespaces **/
// !Do not delete the line above!

class PawsStartUp implements IStartUp
{

    public function configureServices(IServiceProvider $serviceProvider): void
    {
        // Register the Paws layer's capabilities (after core, before the app).
        PawsPermissions::register();
        $serviceProvider->addScoped(BlogPostRepository::class);
        $serviceProvider->addScoped(PageRepository::class);
        $serviceProvider->addScoped(LanguageRepository::class);
        $serviceProvider->addScoped(LocaleResourceRepository::class);
        $serviceProvider->addScoped(PictureRepository::class);
        $serviceProvider->addScoped(MenuItemRepository::class);
        $serviceProvider->addScoped(EmailLogRepository::class);
        $serviceProvider->addScoped(SitemapService::class);
        $serviceProvider->addScoped(EmailService::class);
        $serviceProvider->addScoped(EmailLogService::class);
        $serviceProvider->addScoped(EmailLogCleanupTask::class);
        $serviceProvider->addScoped(LocalizationService::class);
        $serviceProvider->addSingleton(EmailConnector::class);
        $serviceProvider->addScoped(EmailPreviewRegistry::class);
        $serviceProvider->addScoped(IEmailPreviewProvider::class, PawsEmailPreviewProvider::class);
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

        // Framework-level scheduled tasks: prune old email logs daily at midnight.
        CronTab::schedule([EmailLogCleanupTask::class, 'execute'], '0 0 0 * * *');
    }

    public function configureMigrations(IServiceProvider $serviceProvider): void
    {
        ServiceProviderHelper::discover($serviceProvider, [MigrationsMark::folder()]);
        $serviceProvider->addScoped(IMigrationsContext::class, MigrationsContext::class);
    }

    public function configureInstallDependencies(IServiceProvider $serviceProvider): void {}

    public function buildDependencies(IServiceProvider $serviceProvider) {}
}
