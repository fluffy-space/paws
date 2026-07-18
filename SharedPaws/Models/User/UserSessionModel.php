<?php

namespace SharedPaws\Models\User;

use SharedPaws\Models\BaseModel;

/**
 * A user's persistent login session (the AUTH cookie, one UserTokenEntity row).
 * Read-only in the admin: listed under the user edit "Sessions" tab and
 * terminated (deleted) from there. The secret token is never exposed — only its
 * hash, for identification.
 */
class UserSessionModel extends BaseModel
{
    public int $UserId = 0;
    /** sha256 of the session token — an identifier, not the secret. */
    public ?string $TokenHash = null;
    /** Expiry as unix seconds UTC; null = no explicit expiry. */
    public ?int $Expire = null;
    /** Last seen, as microseconds UTC (framework MicroDateTime). */
    public ?int $LastVisit = null;
    /** Signed-in time, microseconds UTC. */
    public int $CreatedOn = 0;
}
