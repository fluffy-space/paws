<?php

namespace Pupils\Components\Views\Admin\Dashboard;

use Pupils\Components\Guards\AdminGuard;
use Viewi\Components\BaseComponent;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\Http\HttpClient;

#[Middleware([AdminGuard::class])]
class Dashboard extends BaseComponent
{
    public string $title = 'Admin dashboard';

    public function __construct(private HttpClient $http) {}

    public function init()
    {
    }
}
