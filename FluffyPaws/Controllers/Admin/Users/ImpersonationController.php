<?php

namespace FluffyPaws\Controllers\Admin\Users;

use Fluffy\Controllers\BaseController;
use Fluffy\Domain\Message\HttpContext;
use Fluffy\Data\Repositories\UserRepository;
use Fluffy\Security\Role;
use Fluffy\Services\Auth\AuthorizationService;

/**
 * Admin "view as user" (impersonation) — SuperAdmin only.
 *
 * Start sets an IMP overlay cookie (just the target id) so the auth flow resolves
 * the target as the effective user; the admin's own session is untouched, no DB
 * write and no signature — it only resolves for a SuperAdmin, who can already act
 * on any account, so it grants no new privilege. Exit clears the cookie. Both are
 * logged (journald / Admin Log Viewer).
 */
class ImpersonationController extends BaseController
{
    function __construct(
        protected AuthorizationService $auth,
        protected UserRepository $users,
        protected HttpContext $httpContext,
    ) {}

    public function Start(int $id)
    {
        if (!$this->auth->hasRole(Role::SuperAdmin)) {
            return $this->Forbidden();
        }
        $admin = $this->auth->getAuthorizedUser();
        if ($admin === null) {
            return $this->Forbidden();
        }
        if ($id === $admin->Id) {
            return $this->BadRequest(['You cannot impersonate yourself.']);
        }
        if ($this->users->getById($id) === null) {
            return $this->NotFound();
        }

        $this->audit('start', $admin->Id, $id);
        $this->auth->startImpersonation($id);
        return ['success' => true, 'redirectUrl' => '/app'];
    }

    public function Exit()
    {
        if (!$this->auth->isImpersonating()) {
            return $this->BadRequest(['Not currently impersonating.']);
        }
        $target = $this->auth->getAuthorizedUser();
        $impersonatorId = $this->auth->getImpersonatorId();
        $targetId = $target?->Id ?? 0;

        $this->audit('stop', (int)$impersonatorId, $targetId);
        $this->auth->stopImpersonation();
        return ['success' => true, 'redirectUrl' => "/admin/user/{$targetId}"];
    }

    /** Persistent audit line (journald / Admin Log Viewer). */
    private function audit(string $action, int $impersonatorId, int $targetId): void
    {
        // Who and whom is the audit trail; the client IP is deliberately not logged.
        echo '[Impersonation] ' . date('Y-m-d H:i:s') . " $action admin=$impersonatorId target=$targetId" . PHP_EOL;
    }
}
