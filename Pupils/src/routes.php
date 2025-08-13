<?php

use Pupils\Components\Views\Admin\Blog\BlogEdit;
use Pupils\Components\Views\Admin\Blog\BlogList;
use Pupils\Components\Views\Admin\Dashboard\Dashboard;
use Pupils\Components\Views\Admin\EmailTemplates\PreviewEmailPage;
use Pupils\Components\Views\Admin\Localization\LanguageEdit;
use Pupils\Components\Views\Admin\Localization\LanguageList;
use Pupils\Components\Views\Admin\MenuItems\MenuItemEdit;
use Pupils\Components\Views\Admin\MenuItems\MenuItemList;
use Pupils\Components\Views\Admin\Page\PageEdit;
use Pupils\Components\Views\Admin\Page\PageList;
use Pupils\Components\Views\Admin\Users\UserEdit;
use Pupils\Components\Views\Admin\Users\UserList;
use Pupils\Components\Views\Auth\EmailVerificationPage;
use Pupils\Components\Views\Auth\Login;
use Pupils\Components\Views\Auth\Register;
use Pupils\Components\Views\Auth\ResetPassword;
use Pupils\Components\Views\Auth\ResetPasswordRequest;
use Pupils\Components\Views\Blog\BlogListPage;
use Pupils\Components\Views\Blog\BlogPostPage;
use Pupils\Components\Views\Content\ContentPage;
use Viewi\App;
use Viewi\Router\Router;

/**
 * @var App $viewiApp
 */
$router = $viewiApp->router();

// Auth
$router->get('/login', Login::class);
$router->get('/register', Register::class);
$router->get('/reset-password', ResetPasswordRequest::class);
$router->get('/account/verified/{failed?}', EmailVerificationPage::class);
$router->get('/password/reset/{code}', ResetPassword::class);

// blog
$router->get('/blog', BlogListPage::class);
$router->get('/blog/{seoName}', BlogPostPage::class);

// admin
$router->lazy('admin', function (Router $router) {
    $router->section('admin', function (Router $router) {
        $router->get('/', Dashboard::class);
        $router->get('/content', PageList::class);
        $router->get('/content/create', PageEdit::class);
        $router->get('/content/{id}', PageEdit::class);

        $router->get('/blog', BlogList::class);
        $router->get('/blog/create', BlogEdit::class);
        $router->get('/blog/{id}', BlogEdit::class);

        $router->get('/user', UserList::class);
        $router->get('/user/create', UserEdit::class);
        $router->get('/user/{id}', UserEdit::class);

        $router->get('/language', LanguageList::class);
        $router->get('/language/create', LanguageEdit::class);
        $router->get('/language/{id}', LanguageEdit::class);

        $router->get('/email-templates', PreviewEmailPage::class);

        // menu items
        $router->get('/menu/{area}', MenuItemList::class);
        $router->get('/menu/{area}/create', MenuItemEdit::class);
        $router->get('/menu/{area}/{id}', MenuItemEdit::class);
    });
});

$router
    ->get('*', ContentPage::class)
    ->priority(-1);
