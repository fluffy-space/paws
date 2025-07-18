<?php

use FluffyPaws\Controllers\Admin\Blog\BlogPostController;
use FluffyPaws\Controllers\Admin\EmailTemplate\EmailTemplateController;
use FluffyPaws\Controllers\Admin\Localization\LanguageController;
use FluffyPaws\Controllers\Admin\Localization\LocaleResourceController;
use FluffyPaws\Controllers\Admin\Media\MediaController;
use FluffyPaws\Controllers\Admin\MenuItem\MenuController;
use FluffyPaws\Controllers\Admin\Page\PageController;
use FluffyPaws\Controllers\Admin\Users\UserController;
use FluffyPaws\Controllers\AuthorizationController;
use FluffyPaws\Controllers\BlogController;
use FluffyPaws\Controllers\ContentController;
use FluffyPaws\Controllers\LocalizationController;
use FluffyPaws\Controllers\MiscController;
use FluffyPaws\Controllers\SitemapController;
use Viewi\App;
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
        $router->get('user/{id}', [UserController::class, 'Get']);
        $router->put('user/{id}', [UserController::class, 'Update']);
        $router->delete('user/{id}', [UserController::class, 'Delete']);

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
        $router->get('email-template/preview/{template}', [EmailTemplateController::class, 'GetPreview']);

        // menu items
        $router->get('menu', [MenuController::class, 'List']);
        $router->post('menu', [MenuController::class, 'Create']);
        $router->get('menu/{id}', [MenuController::class, 'Get']);
        $router->put('menu/{id}', [MenuController::class, 'Update']);
        $router->delete('menu/{id}', [MenuController::class, 'Delete']);
    });
});

// Viewi application
include __DIR__ . '/../Pupils/src/routes.php';
