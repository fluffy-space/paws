<?php

namespace Pupils\Components\Guards;

use Pupils\Components\Services\Auth\AuthService;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use Viewi\Components\Middleware\IMIddleware;
use Viewi\Components\Middleware\IMIddlewareContext;
use Viewi\Components\Routing\ClientRoute;
use Viewi\DI\Singleton;

/**
 * Base admin-area guard: the user must be able to reach the admin area
 * (the AccessAdmin capability, surfaced server-side as User::$CanAccessAdmin).
 *
 * Feature pages should use HasCapability via the Middleware attribute, e.g.
 * #[Middleware([[HasCapability::class, 'ManageBlog']])], which keeps this gate
 * and additionally requires a specific capability.
 */
#[Singleton]
class AdminGuard implements IMIddleware
{
    public function __construct(public ClientRoute $route, public AuthService $auth) {}

    public function run(IMIddlewareContext $c)
    {
        $this->auth->getUserSession(function (UserAuthSessionModel $session) use ($c) {
            if ($this->authorize($session)) {
                $c->next();
            } else {
                $c->next(false); // cancel
                $this->route->navigate('/login'); // redirect
            }
        });
    }

    /** Whether the current session may access the guarded page. */
    public function authorize(UserAuthSessionModel $session): bool
    {
        return $session->user?->CanAccessAdmin === true;
    }
}
