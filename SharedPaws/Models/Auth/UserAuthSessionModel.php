<?php

namespace SharedPaws\Models\Auth;

class UserAuthSessionModel
{
    public bool $isAuthenticated = false;
    public ?UserViewModel $user = null;

    /**
     * Stable system names of the roles assigned to the user, e.g. ["Admin", "TeamOwner"].
     * Resolved server-side from the Permissions bitmask.
     * @var string[]
     */
    public array $roles = [];

    /**
     * Effective capability names the user has, e.g. ["AccessAdmin", "CreateShortUrl"].
     * Lets the client gate UI without doing 64-bit bitmask math in JS.
     * @var string[]
     */
    public array $capabilities = [];

    /** True when this session is running under an admin "view as user" overlay. */
    public bool $impersonating = false;

    /** Display name of the acting admin while impersonating (drives the banner). */
    public ?string $impersonatorName = null;
}
