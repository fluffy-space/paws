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
            // canUseApp(), not `$user->Active`: Active is false for every brand-new signup
            // (only confirmation flips it) while login already admits those users, so gating on
            // it sent them round a silent loop — sign in, get bounced back to /login, with
            // nothing on screen saying why. An unconfirmed account gets in and is nagged by the
            // app's verify-email notice; the actions that matter stay blocked server-side.
            if ($this->auth->canUseApp($session)) {
                $c->next();
            } else {
                $c->next(false); // cancel
                // Carry the intended path, so signing in finishes the errand the visitor came
                // for instead of dropping them on the home page with nothing said. A query
                // parameter, not browser storage: this guard also runs during SSR (a link opened
                // directly, e.g. "keep this link" on an anonymous short URL), where there is no
                // session storage to write to. Login and Register read it back.
                $path = $this->route->getUrlPath();
                $to = '/login';
                if ($path !== null && $path !== '' && $path !== '/login') {
                    $to = '/login?redirect=' . rawurlencode($path);
                }
                $this->route->navigate($to); // redirect
            }
        });
    }
}
