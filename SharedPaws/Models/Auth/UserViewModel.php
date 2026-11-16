<?php

namespace SharedPaws\Models\Auth;

class UserViewModel
{
    public int $Id;
    public string $UserName;
    public ?string $FirstName = null;
    public ?string $LastName = null;
    public ?string $Email = null;
    public ?string $Phone = null;
    public bool $Active = false;
    public bool $EmailConfirmed = false;
    /** IANA zone the person reads dates in; null until set (readers treat null as UTC). */
    public ?string $TimeZone = null;
    /**
     * The zone's UTC offsets as [[fromUnixSeconds, offsetSeconds], ...], oldest first (TimeZones::offsets).
     * The browser formats dates with this table, so it agrees with the server render.
     * @var int[][]
     */
    public array $TimeZoneOffsets = [];
    /** Derived server-side from Permissions (Capability::AccessAdmin); used by guards/redirects. */
    public bool $CanAccessAdmin = false;
}
