<?php

namespace FluffyPaws\Controllers\Admin\Users;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Entities\Auth\UserTokenEntity;
use Fluffy\Data\Entities\Auth\UserTokenEntityMap;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Repositories\UserTokenRepository;
use Fluffy\Services\Auth\AuthorizationService;
use FluffyPaws\Security\PawsCapability;
use SharedPaws\Models\User\UserSessionModel;

/**
 * Admin management of a user's login sessions (UserToken rows — the AUTH cookie).
 *
 * Surfaced only inside the user edit page "Sessions" tab (no menu / standalone
 * list): List is always scoped to one user via ?userId=, and Delete terminates a
 * single session — the same effect as that user logging out on that device.
 *
 * Gated on ManageUsers: managing a user's sessions is part of managing the user.
 * The secret token is never returned to the client, only its hash.
 */
class UserSessionController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected UserTokenRepository $tokens,
        protected AuthorizationService $auth,
    ) {}

    public function List(int $page = 1, int $size = 10, ?string $search = null, ?int $userId = null)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageUsers)) {
            return $this->Forbidden();
        }
        // Sessions are only ever listed for a specific user (the edit tab). Without a
        // user scope there is nothing to show — never dump every user's tokens.
        if ($userId === null) {
            return ['list' => [], 'total' => 0];
        }
        $where = [[UserTokenEntityMap::PROPERTY_UserId, $userId]];
        $entities = $this->tokens->search($where, [UserTokenEntityMap::PROPERTY_CreatedOn => -1], $page, $size);
        $models = array_map(fn($entity) => $this->mapper->map(UserSessionModel::class, $entity), $entities['list']);
        return ['list' => $models, 'total' => $entities['total']];
    }

    public function Delete(int $id)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageUsers)) {
            return $this->Forbidden();
        }
        /** @var ?UserTokenEntity $entity */
        $entity = $this->tokens->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        $success = $this->tokens->delete($entity);
        return $success;
    }
}
