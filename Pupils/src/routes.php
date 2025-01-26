<?php

use Pupils\Components\Views\Auth\Login;
use Viewi\App;
use Viewi\Components\Http\Message\Response;
use Viewi\Router\Router;

/**
 * @var App $viewiApp
 */
$router = $viewiApp->router();

// Auth
$router->get('/login', Login::class);