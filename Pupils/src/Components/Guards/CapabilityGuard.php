<?php

namespace Pupils\Components\Guards;

use SharedPaws\Models\Auth\UserAuthSessionModel;

/**
 * Admin guard that additionally requires a specific capability.
 *
 * The page must be reachable (AccessAdmin, via AdminGuard) AND the user must
 * hold the capability named by capability(). The name is the stable capability
 * name shipped to the client in session.capabilities (registered server-side
 * via PermissionRegistry::defineCapability) — the client cannot do 64-bit
 * bitmask math in JS, so it gates on names.
 */
abstract class CapabilityGuard extends AdminGuard
{
    /** Stable capability name, e.g. 'ManageUsers' (matches the server registration). */
    abstract public function capability(): string;

    public function authorize(UserAuthSessionModel $session): bool
    {
        return parent::authorize($session)
            && in_array($this->capability(), $session->capabilities, true);
    }
}
