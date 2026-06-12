<?php

namespace SharedPaws\Models\User;

/**
 * A single assignable role for the admin user-edit checkboxes.
 *
 * Bit is the role bit (always <= 1<<15, safe for JS). The client only toggles
 * Selected; the server recomputes the user's Permissions bitmask from the
 * selected bits (Permissions can use bits up to 62, which JS bitwise can't handle).
 */
class RoleOptionModel
{
    public int $Bit = 0;
    public string $Label = '';
    public bool $Selected = false;
}
