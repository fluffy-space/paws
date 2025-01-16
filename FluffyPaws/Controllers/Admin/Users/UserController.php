<?php

namespace FluffyPaws\Controllers\Admin\Users;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Entities\Auth\UserEntity;
use Fluffy\Data\Entities\Auth\UserEntityMap;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Repositories\UserRepository;
use Fluffy\Services\Auth\AuthorizationService;
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
        return $model;
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
        return $success ? $this->mapper->map(UserModel::class, $entity) : null;
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
        return $success ? $this->mapper->map(UserModel::class, $entity) : null;
    }
}
