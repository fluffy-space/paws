<?php

namespace FluffyPaws\Controllers\Admin\Users;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Entities\Auth\UserEntity;
use Fluffy\Data\Entities\Auth\UserEntityMap;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Repositories\UserRepository;
use Fluffy\Security\Capability;
use Fluffy\Security\Permissions;
use Fluffy\Security\PermissionRegistry;
use Fluffy\Security\Role;
use Fluffy\Services\Auth\AuthorizationService;
use SharedPaws\Models\User\RoleOptionModel;
use SharedPaws\Models\User\UserModel;
use SharedPaws\Models\User\UserValidation;

class UserController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected UserRepository $users,
        protected AuthorizationService $auth
    ) {}

    public function List(int $page = 1, int $size = 10, ?string $search = null)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $search = trim($search ?? '');
        $where = [];
        if ($search) {
            $search = strtolower($search);
            $parts = explode(' ', $search);
            foreach ($parts as $part) {
                if (trim($part)) {
                    $where[] = [
                        [UserEntityMap::PROPERTY_FirstName, 'like', "%$part%"],
                        [UserEntityMap::PROPERTY_LastName, 'like', "%$part%"],
                        [UserEntityMap::PROPERTY_UserName, 'like', "%$part%"],
                        [UserEntityMap::PROPERTY_Email, 'like', "%$part%"],
                        [UserEntityMap::PROPERTY_Phone, 'like', "%$part%"]
                    ];
                }
            }
        }
        $entities = $this->users->search($where, [UserEntityMap::PROPERTY_CreatedOn => 1], $page, $size);
        $models = array_map(fn($entity) => $this->mapper->map(UserModel::class, $entity), $entities['list']);
        return ['list' => $models, 'total' => $entities['total']];
    }

    public function Get(int $id)
    {
        $entity = $this->users->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        /**
         * @var UserModel $model
         */
        $model = $this->mapper->map(UserModel::class, $entity);
        $model->Roles = $this->roleOptions($entity->Permissions);
        return $model;
    }

    /** Role catalog for the create form (all unselected). */
    public function Roles()
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        return $this->roleOptions(0);
    }

    /**
     * Build the assignable-role catalog (core + app), marking which are set in $permissions.
     * @return RoleOptionModel[]
     */
    private function roleOptions(int $permissions): array
    {
        $options = [];
        foreach (PermissionRegistry::roleLabels() as $bit => $label) {
            $option = new RoleOptionModel();
            $option->Bit = $bit;
            $option->Label = $label;
            $option->Selected = Permissions::hasRole($permissions, $bit);
            $options[] = $option;
        }
        return $options;
    }

    /**
     * Apply the submitted role selection onto $entity->Permissions, server-side.
     *
     * - Requires the editor to have ManageRoles; otherwise role changes are ignored.
     * - Only a SuperAdmin may set/clear the SuperAdmin role (anti-escalation).
     * - Preserves any direct capability bits (above the role region).
     * - Reads each option defensively (stdClass from the request body, or array).
     */
    private function applyRoles(UserEntity $entity, UserModel $user): void
    {
        if (!$this->auth->can(Capability::ManageRoles)) {
            return;
        }
        $editorIsSuperAdmin = Permissions::hasRole($this->auth->permissions(), Role::SuperAdmin);
        $catalog = PermissionRegistry::roleLabels();
        $selected = 0;
        foreach ($user->Roles as $role) {
            $bit = (int) (is_array($role) ? ($role['Bit'] ?? 0) : ($role->Bit ?? 0));
            $isSelected = (bool) (is_array($role) ? ($role['Selected'] ?? false) : ($role->Selected ?? false));
            if (!isset($catalog[$bit])) {
                continue; // unknown / non-role bit
            }
            if ($bit === Role::SuperAdmin && !$editorIsSuperAdmin) {
                // Cannot grant or revoke SuperAdmin; preserve the user's existing state.
                if (Permissions::hasRole($entity->Permissions, Role::SuperAdmin)) {
                    $selected |= Role::SuperAdmin;
                }
                continue;
            }
            if ($isSelected) {
                $selected |= $bit;
            }
        }
        $entity->Permissions = ($entity->Permissions & ~Permissions::ROLE_MASK) | $selected;
    }

    public function Update(int $id, UserModel $user)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new UserValidation($user))->getValidationRules();
        foreach ($validationRules as $property => $rules) {
            foreach ($rules as $validationRule) {
                $validationResult = $validationRule();
                if ($validationResult !== true) {
                    $validationMessages[] = $validationResult === false ? "Validation has failed for $property." : $validationResult;
                }
            }
        }
        if (count($validationMessages) > 0) {
            return $this->BadRequest($validationMessages);
        }
        // DB validations        
        $entity = $this->users->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        /**
         * @var UserEntity $entity
         */
        $entity = $this->mapper->map(UserEntity::class, $user, $entity);
        $this->applyRoles($entity, $user);
        $entity->Email = $entity->Email ? strtolower($entity->Email) : null;
        $entity->Phone = $entity->Phone ? strtolower($entity->Phone) : null;
        $entity->UserName = $entity->Email ? $entity->Email : $entity->Phone;
        $search = [[UserEntityMap::PROPERTY_UserName, $entity->UserName]];
        if ($entity->Email) {
            $search[] = [UserEntityMap::PROPERTY_Email, $entity->Email];
        }
        if ($entity->Phone) {
            $search[] = [UserEntityMap::PROPERTY_Phone, $entity->Phone];
        }
        /**
         * @var ?UserEntity $existentUser
         */
        $existentUser = $this->users->firstOrDefault(
            [$search]
        );
        if ($existentUser !== null && $existentUser->Id !== $entity->Id) {
            return $this->BadRequest([
                "User with such email or phone already exists."
            ]);
        }
        if ($user->NewPassword && $user->ConfirmPassword === $user->NewPassword) {
            $entity->Password = $this->auth->hashPassword($user->NewPassword);
        }

        $success = $this->users->update($entity);
        if (!$success) {
            return null;
        }
        $result = $this->mapper->map(UserModel::class, $entity);
        $result->Roles = $this->roleOptions($entity->Permissions);
        return $result;
    }

    public function Delete(int $id)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $entity = $this->users->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        $success = $this->users->delete($entity);
        return $success;
    }

    public function Create(UserModel $user)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new UserValidation($user))->getValidationRules();
        foreach ($validationRules as $property => $rules) {
            foreach ($rules as $validationRule) {
                $validationResult = $validationRule();
                if ($validationResult !== true) {
                    $validationMessages[] = $validationResult === false ? "Validation has failed for $property." : $validationResult;
                }
            }
        }
        if (count($validationMessages) > 0) {
            return $this->BadRequest($validationMessages);
        }
        /**
         * @var UserEntity $entity
         */
        $entity = $this->mapper->map(UserEntity::class, $user);
        $this->applyRoles($entity, $user);
        $entity->Email = $entity->Email ? strtolower($entity->Email) : null;
        $entity->Phone = $entity->Phone ? strtolower($entity->Phone) : null;
        $entity->UserName = $entity->Email ? $entity->Email : $entity->Phone;
        $search = [[UserEntityMap::PROPERTY_UserName, $entity->UserName]];
        if ($entity->Email) {
            $search[] = [UserEntityMap::PROPERTY_Email, $entity->Email];
        }
        if ($entity->Phone) {
            $search[] = [UserEntityMap::PROPERTY_Phone, $entity->Phone];
        }
        /**
         * @var ?UserEntity $existentUser
         */
        $existentUser = $this->users->firstOrDefault(
            [$search]
        );
        if ($existentUser !== null) {
            return $this->BadRequest([
                "User with such email or phone already exists."
            ]);
        }
        if ($user->NewPassword && $user->ConfirmPassword === $user->NewPassword) {
            $entity->Password = $this->auth->hashPassword($user->NewPassword);
        }
        $success = $this->users->create($entity);
        if (!$success) {
            return null;
        }
        $result = $this->mapper->map(UserModel::class, $entity);
        $result->Roles = $this->roleOptions($entity->Permissions);
        return $result;
    }
}
