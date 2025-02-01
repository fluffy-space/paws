<?php

namespace Pupils\Components\Guards;

use Pupils\Components\Services\Auth\AuthService;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use Viewi\Components\Middleware\IMIddleware;
use Viewi\Components\Middleware\IMIddlewareContext;
use Viewi\Components\Routing\ClientRoute;
use Viewi\DI\Singleton;

#[Singleton]
class AdminGuard implements IMIddleware
{
    public function __construct(private ClientRoute $route, private AuthService $auth) {}

    public function run(IMIddlewareContext $c)
    {
        $this->auth->getUserSession(function (UserAuthSessionModel $session) use ($c) {
            if ($session->user?->IsAdmin) {
                $c->next();
            } else {
                $c->next(false); // cancel
                $this->route->navigate('/login'); // redirect
            }
        });
    }
}
