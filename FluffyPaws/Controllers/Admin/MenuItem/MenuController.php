<?php

namespace FluffyPaws\Controllers\Admin\MenuItem;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Services\Auth\AuthorizationService;
use Fluffy\Swoole\Cache\CacheManager;
use FluffyPaws\Data\Entities\Menu\MenuItemEntity;
use FluffyPaws\Data\Entities\Menu\MenuItemEntityMap;
use FluffyPaws\Data\Repositories\MenuItemRepository;
use SharedPaws\Models\MenuItem\MenuItemModel;
use SharedPaws\Models\MenuItem\MenuItemValidation;

class MenuController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected MenuItemRepository $menuItems,
        protected AuthorizationService $auth,
        protected CacheManager $cache
    ) {}

    public function List(int $page = 1, int $size = 10, ?int $location = null, ?string $search = null)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $search = trim($search ?? '');
        $where = $location !== null ? [[MenuItemEntityMap::PROPERTY_Location, $location]] : [];
        if ($search) {
            $where[] = [MenuItemEntityMap::PROPERTY_Title, 'like', "%$search%"];
        }
        $entities = $this->menuItems->search($where, ['Row' => 1, 'Column' => 1, MenuItemEntityMap::PROPERTY_Order => 1, MenuItemEntityMap::PROPERTY_Id => 1], $page, $size);
        $models = array_map(fn($entity) => $this->mapper->map(MenuItemModel::class, $entity), $entities['list']);
        return ['list' => $models, 'total' => $entities['total']];
    }

    public function Get(int $id)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $entity = $this->menuItems->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        /**
         * @var MenuItemModel $model
         */
        $model = $this->mapper->map(MenuItemModel::class, $entity);
        return $model;
    }

    public function Update(int $id, MenuItemModel $item)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new MenuItemValidation($item))->getValidationRules();
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
        $menuItemEntity = $this->menuItems->getById($id);
        if (!$menuItemEntity) {
            return $this->NotFound();
        }
        /**
         * @var MenuItemEntity $menuItemEntity
         */
        $menuItemEntity = $this->mapper->map(MenuItemEntity::class, $item, $menuItemEntity);
        $success = $this->menuItems->update($menuItemEntity);
        $cacheKey = sprintf(MenuItemEntityMap::CACHE_KEY, $menuItemEntity->Location);
        $this->cache->delete($cacheKey);
        return $success ? $this->mapper->map(MenuItemModel::class, $menuItemEntity) : null;
    }

    public function Delete(int $id)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        /**
         * @var MenuItemEntity $menuItemEntity
         */
        $menuItemEntity = $this->menuItems->getById($id);
        if (!$menuItemEntity) {
            return $this->NotFound();
        }
        $success = $this->menuItems->delete($menuItemEntity);
        $cacheKey = sprintf(MenuItemEntityMap::CACHE_KEY, $menuItemEntity->Location);
        $this->cache->delete($cacheKey);
        return $success;
    }

    public function Create(MenuItemModel $item)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new MenuItemValidation($item))->getValidationRules();
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
        /**
         * @var MenuItemEntity $menuItemEntity
         */
        $menuItemEntity = $this->mapper->map(MenuItemEntity::class, $item);
        $success = $this->menuItems->create($menuItemEntity);
        $cacheKey = sprintf(MenuItemEntityMap::CACHE_KEY, $menuItemEntity->Location);
        $this->cache->delete($cacheKey);
        return $success ? $this->mapper->map(MenuItemModel::class, $menuItemEntity) : null;
    }
}
