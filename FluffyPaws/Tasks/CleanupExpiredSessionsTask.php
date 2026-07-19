<?php

namespace FluffyPaws\Tasks;

use Fluffy\Data\Entities\Auth\UserTokenEntityMap;
use Fluffy\Data\Repositories\UserTokenRepository;

/**
 * Retention GC for expired login sessions (UserToken rows whose Expire has
 * passed). Pure row hygiene, NOT enforcement — AuthorizationService::authorizeRequest
 * already rejects (and deletes) an expired token on use; this just reclaims rows
 * for users who never log in again (the per-login prune only touches active users).
 *
 * Scheduled daily from PawsStartUp::configure. Runs inside the Swoole task scope
 * (DB access is valid there). Deletes in a single statement; logs only when it
 * actually removed rows (no spam on idle ticks). Rows with a NULL Expire (no
 * explicit expiry) are left untouched.
 */
class CleanupExpiredSessionsTask
{
    public function __construct(private UserTokenRepository $tokens) {}

    public function execute()
    {
        $removed = $this->tokens->deleteWhere([
            [UserTokenEntityMap::PROPERTY_Expire, '<', time()],
        ]);
        if ($removed > 0) {
            echo '[CleanupExpiredSessionsTask] ' . date('Y-m-d H:i:s') . " pruned $removed expired session(s)." . PHP_EOL;
        }
    }
}
