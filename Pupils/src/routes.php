<?php

use Pupils\Components\Views\Admin\Dashboard\Dashboard;
use Pupils\Components\Views\Admin\Page\PageList;
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
    });
});
