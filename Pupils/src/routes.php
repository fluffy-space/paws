<?php

use Pupils\Components\Views\Admin\Blog\BlogEdit;
use Pupils\Components\Views\Admin\Blog\BlogList;
use Pupils\Components\Views\Admin\Dashboard\Dashboard;
use Pupils\Components\Views\Admin\Page\PageEdit;
use Pupils\Components\Views\Admin\Page\PageList;
use Pupils\Components\Views\Admin\Users\UserEdit;
use Pupils\Components\Views\Admin\Users\UserList;
use Pupils\Components\Views\Auth\Login;
use Viewi\App;
use Viewi\Router\Router;

/**
 * @var App $viewiApp
 */
$router = $viewiApp->router();

// Auth
$router->get('/login', Login::class);

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
    });
});
