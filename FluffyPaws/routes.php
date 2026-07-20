<?php

use FluffyPaws\Controllers\Admin\Blog\BlogPostController;
use FluffyPaws\Controllers\Admin\EmailLog\EmailLogController;
use FluffyPaws\Controllers\Admin\EmailTemplate\EmailTemplateController;
use FluffyPaws\Controllers\Admin\Localization\LanguageController;
use FluffyPaws\Controllers\Admin\Localization\LocaleResourceController;
use FluffyPaws\Controllers\Admin\Media\MediaController;
use FluffyPaws\Controllers\Admin\MenuItem\MenuController;
use FluffyPaws\Controllers\Admin\Page\PageController;
use FluffyPaws\Controllers\Admin\Settings\SettingController;
use FluffyPaws\Controllers\Admin\Users\UserController;
use FluffyPaws\Controllers\Admin\Users\UserSessionController;
use FluffyPaws\Controllers\Admin\Users\ImpersonationController;
use FluffyPaws\Controllers\AuthorizationController;
use FluffyPaws\Controllers\BlogController;
use FluffyPaws\Controllers\ContentController;
use FluffyPaws\Controllers\LocalizationController;
use FluffyPaws\Controllers\MiscController;
use FluffyPaws\Controllers\SitemapController;
use Viewi\App;
use Viewi\Components\Http\Message\Response;
use Viewi\Router\Router;

/**
 * @var App $viewiApp
 */
$router = $viewiApp->router();

$router->get('/account/confirm/{code}', [AuthorizationController::class, 'ConfirmEmail']);

// Sitemap
$router->get('/sitemap.xml', [SitemapController::class, 'Sitemap']);
$router->get('/robots.txt', [SitemapController::class, 'Robots']);

$router->section('/api/', function (Router $router) {
    // Content
    $router->get('content', [ContentController::class, 'GetByPath']);

    // Blog
    $router->get('blog', [BlogController::class, 'GetList']);
    $router->get('blog/{seoName}', [BlogController::class, 'GetBySeoName']);

    // Menu
    $router->get('menu/{location}', [MiscController::class, 'GetMenuItems']);

    // Localization
    $router->get('locale-resource/{languageId}', [LocalizationController::class, 'GetResources']);

    // Auth
    $router->get('authorization/me', [AuthorizationController::class, 'Me']);

    $router->post('authorization/session', [AuthorizationController::class, 'Session']);
    $router->post('authorization/login', [AuthorizationController::class, 'Login']);
    $router->post('authorization/logout', [AuthorizationController::class, 'Logout']);

    // Exit impersonation — authorized by the IMP overlay itself (the effective
    // user isn't an admin), so it sits outside the admin block.
    $router->post('impersonation/exit', [ImpersonationController::class, 'Exit']);
    $router->post('authorization/register', [AuthorizationController::class, 'Register']);
    $router->post('authorization/reset-password', [AuthorizationController::class, 'ResetPassword']);
    $router->post('authorization/reset-password-confirm', [AuthorizationController::class, 'ResetPasswordConfirm']);

    /*  ADMIN AREA */
    $router->section('admin/', function (Router $router) {
        // blog
        $router->get('blog', [BlogPostController::class, 'List']);
        $router->post('blog', [BlogPostController::class, 'Create']);
        $router->get('blog/{id}', [BlogPostController::class, 'Get']);
        $router->put('blog/{id}', [BlogPostController::class, 'Update']);
        $router->delete('blog/{id}', [BlogPostController::class, 'Delete']);

        // content
        $router->get('content', [PageController::class, 'List']);
        $router->post('content', [PageController::class, 'Create']);
        $router->get('content/{id}', [PageController::class, 'Get']);
        $router->put('content/{id}', [PageController::class, 'Update']);
        $router->delete('content/{id}', [PageController::class, 'Delete']);

        // users
        $router->get('user', [UserController::class, 'List']);
        $router->post('user', [UserController::class, 'Create']);
        $router->get('user/roles', [UserController::class, 'Roles']);
        $router->get('user/{id}', [UserController::class, 'Get']);
        $router->put('user/{id}', [UserController::class, 'Update']);
        $router->delete('user/{id}', [UserController::class, 'Delete']);

        // user login sessions (AUTH tokens) — managed from the user edit "Sessions"
        // tab (scoped by ?userId=); terminate = delete. Distinct 'user-session'
        // segment, so it does not collide with 'user/{id}'.
        $router->get('user-session', [UserSessionController::class, 'List']);
        $router->delete('user-session/{id}', [UserSessionController::class, 'Delete']);

        // impersonation start ("view as user") — SuperAdmin/ImpersonateUsers. Exit
        // lives outside the admin block (the effective user isn't an admin then).
        $router->post('user/{id}/impersonate', [ImpersonationController::class, 'Start']);

        // localization
        $router->get('language', [LanguageController::class, 'List']);
        $router->post('language', [LanguageController::class, 'Create']);
        $router->get('language/{id}', [LanguageController::class, 'Get']);
        $router->put('language/{id}', [LanguageController::class, 'Update']);
        $router->delete('language/{id}', [LanguageController::class, 'Delete']);

        $router->get('locale-resource', [LocaleResourceController::class, 'List']);
        $router->post('locale-resource', [LocaleResourceController::class, 'Create']);
        $router->get('locale-resource/{id}', [LocaleResourceController::class, 'Get']);
        $router->put('locale-resource/{id}', [LocaleResourceController::class, 'Update']);
        $router->delete('locale-resource/{id}', [LocaleResourceController::class, 'Delete']);

        // Media
        $router->post('picture/upload', [MediaController::class, 'Upload']);

        // Email templates
        $router->get('email-template/templates', [EmailTemplateController::class, 'Templates']);
        $router->get('email-template/preview/{template}', [EmailTemplateController::class, 'GetPreview']);

        // Email logs (read-only + delete; reuses ManageEmailTemplates capability)
        $router->get('email-log', [EmailLogController::class, 'List']);
        $router->get('email-log/{id}/body', [EmailLogController::class, 'GetBody']);
        $router->get('email-log/{id}', [EmailLogController::class, 'Get']);
        $router->delete('email-log/{id}', [EmailLogController::class, 'Delete']);

        // settings (runtime settings store; ManageSettings / SuperAdmin only)
        $router->get('setting', [SettingController::class, 'List']);
        $router->post('setting', [SettingController::class, 'Create']);
        $router->get('setting/{id}', [SettingController::class, 'Get']);
        $router->put('setting/{id}', [SettingController::class, 'Update']);
        $router->delete('setting/{id}', [SettingController::class, 'Delete']);

        // menu items
        $router->get('menu', [MenuController::class, 'List']);
        $router->post('menu', [MenuController::class, 'Create']);
        $router->get('menu/{id}', [MenuController::class, 'Get']);
        $router->put('menu/{id}', [MenuController::class, 'Update']);
        $router->delete('menu/{id}', [MenuController::class, 'Delete']);
    });

    $router->register('*', '*', function () {
        return new Response('', 404, 'Not Found', [], 'Not Found');
    })->priority(-100);
});

// Viewi application
include __DIR__ . '/../Pupils/src/routes.php';
