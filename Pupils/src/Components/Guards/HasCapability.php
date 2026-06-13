<?php

namespace Pupils\Components\Guards;

use Pupils\Components\Services\Auth\AuthService;
use SharedPaws\Models\Auth\UserAuthSessionModel;
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
}
