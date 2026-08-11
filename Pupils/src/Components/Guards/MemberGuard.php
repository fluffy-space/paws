<?php

namespace Pupils\Components\Guards;

use Pupils\Components\Services\Auth\AuthService;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use Viewi\Components\Middleware\IMIddleware;
use Viewi\Components\Middleware\IMIddlewareContext;
use Viewi\Components\Routing\ClientRoute;
use Viewi\DI\Singleton;

#[Singleton]
class MemberGuard implements IMIddleware
{
    public function __construct(private ClientRoute $route, private AuthService $auth) {}

    public function run(IMIddlewareContext $c)
    {
        $this->auth->getUserSession(function (UserAuthSessionModel $session) use ($c) {
            // Active alone would lock out every new signup: registration creates the user
            // Active=false, and only email confirmation flips it. Login already admits those
            // users ("mid-signup, not deactivated" — AuthorizationService::isDeactivated), so
            // gating the app on Active sent them round a silent loop: sign in, get bounced
            // back to /login, with nothing on screen saying why. Mirror the login rule here —
            // an unconfirmed account gets in and is nagged by VerifyEmailNotice, while the
            // actions that matter stay blocked server-side (ResolvesTeam).
            $user = $session->user;
            $deactivated = $user !== null && !$user->Active && $user->EmailConfirmed;
            if ($user !== null && !$deactivated) {
                $c->next();
            } else {
                $c->next(false); // cancel
                $this->route->navigate('/login'); // redirect
            }
        });
    }
}
