<?php

namespace FluffyPaws\Controllers\Admin\Users;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Entities\Auth\UserEntity;
use Fluffy\Data\Repositories\UserRepository;
use Fluffy\Security\Role;
use Fluffy\Services\Auth\AuthorizationService;
use Fluffy\Services\Auth\TwoFactorService;
use FluffyPaws\Security\PawsCapability;
use Fluffy\Data\Mapper\IMapper;
use FluffyPaws\Services\Emails\EmailService;
use SharedPaws\Models\Auth\UserViewModel;
use SharedPaws\Models\Auth\TwoFactorStatusModel;

/**
 * Two-factor sign-in on the admin user page: see whether a user has it on, and reset it for
 * someone locked out (lost phone and recovery codes).
 *
 * Reset is SuperAdmin-only, like impersonation: it takes a factor off an account. It also signs
 * the user out everywhere and emails them, so a reset nobody asked for is noticed. Logged to
 * journald like impersonation; the audit log is per workspace and this is about an account.
 */
class UserTwoFactorController extends BaseController
{
    function __construct(
        protected AuthorizationService $auth,
        protected TwoFactorService $twoFactor,
        protected UserRepository $users
    ) {}

    /** GET /api/admin/user/{id}/two-factor */
    public function Get(int $id)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageUsers)) {
            return $this->Forbidden();
        }
        /** @var UserEntity|null $user */
        $user = $this->users->getById($id);
        if ($user === null) {
            return $this->NotFound();
        }
        $status = new TwoFactorStatusModel();
        $status->Enabled = $this->twoFactor->isEnabled($id);
        $status->RecoveryCodesLeft = $status->Enabled ? $this->twoFactor->recoveryCodesLeft($id) : 0;
        $status->HasPassword = $user->Password !== null && $user->Password !== '';
        $status->Required = $this->auth->twoFactorRequiredFor($user);
        return $status;
    }

    /** POST /api/admin/user/{id}/two-factor/reset */
    public function Reset(int $id, EmailService $emails, IMapper $mapper)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageUsers) || !$this->auth->hasRole(Role::SuperAdmin)) {
            return $this->Forbidden();
        }
        if ($this->auth->isImpersonating()) {
            return $this->Forbidden('Not available while viewing as another user.');
        }
        /** @var UserEntity|null $user */
        $user = $this->users->getById($id);
        if ($user === null) {
            return $this->NotFound();
        }
        if (!$this->twoFactor->isEnabled($id)) {
            return $this->BadRequest(['Two-factor sign-in is not on for this user.']);
        }
        $this->twoFactor->disable($id);
        $this->auth->revokeSessions($id);
        $admin = $this->auth->getRealUser();
        echo '[TwoFactor] ' . date('Y-m-d H:i:s') . ' reset admin=' . ($admin?->Id ?? 0) . " target=$id" . PHP_EOL;
        $emails->dispatchTwoFactorChangedEmail($mapper->map(UserViewModel::class, $user), false, true);
        return ['success' => true];
    }
}
