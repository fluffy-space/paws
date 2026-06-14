<?php

namespace Pupils\Components\Guards;

use Pupils\Components\Services\Auth\AuthService;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use Viewi\Components\Middleware\IMIddlewareContext;
use Viewi\Components\Routing\ClientRoute;

/**
 * Admin guard that additionally requires a specific capability, supplied
 * inline via the Middleware attribute — no per-capability subclass needed:
 *
 *   #[Middleware([[HasCapability::class, 'ManageBlog']])]
 *
 * The capability literal is zipped onto $capability at build time and injected
 * by resolve() at runtime (client and SSR).
 *
 * NOT a singleton on purpose: resolve() caches singletons by class name and
 * ignores constructor params, so a shared instance would freeze on the first
 * capability it saw. Each guarded page resolves its own transient instance.
 *
 * The name must match the stable capability name shipped to the client in
 * session.capabilities (registered server-side via
 * PermissionRegistry::defineCapability) — the client gates on names because it
 * cannot do 64-bit bitmask math in JS.
 */
class HasCapability extends AdminGuard
{
    public function __construct(public string $capability, ClientRoute $route, AuthService $auth)
    {
        parent::__construct($route, $auth);
    }

    public function authorize(UserAuthSessionModel $session): bool
    {
        return parent::authorize($session)
            && in_array($this->capability, $session->capabilities, true);
    }

    /**
     * Three-way redirect, unlike AdminGuard's single /login fallback:
     *  - capability granted        -> proceed
     *  - admin access, but missing the capability -> stay inside the admin
     *    area on an "insufficient permissions" page (the user is signed in and
     *    allowed here, they just can't see this feature)
     *  - no admin access at all     -> /login
     */
    public function run(IMIddlewareContext $c)
    {
        $this->auth->getUserSession(function (UserAuthSessionModel $session) use ($c) {
            if ($this->authorize($session)) {
                $c->next();
            } else {
                $c->next(false); // cancel
                // Inline the admin-access check rather than calling
                // parent::authorize() here: the transpiler turns parent:: into
                // JS `super`, which is illegal inside this nested callback.
                if ($session->user?->CanAccessAdmin === true) {
                    $this->route->navigate('/admin/access-denied');
                } else {
                    $this->route->navigate('/login');
                }
            }
        });
    }
}
