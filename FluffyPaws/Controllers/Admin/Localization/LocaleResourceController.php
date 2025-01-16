<?php

namespace FluffyPaws\Controllers\Admin\Localization;

use FluffyPaws\Data\Entities\Localization\LocaleResourceEntity;
use FluffyPaws\Data\Entities\Localization\LocaleResourceEntityMap;
use FluffyPaws\Data\Repositories\LocaleResourceRepository;
use Fluffy\Controllers\BaseController;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Services\Auth\AuthorizationService;
use Fluffy\Swoole\Cache\CacheManager;
use SharedPaws\Models\Localization\LocaleResourceModel;
use SharedPaws\Models\Localization\LocaleResourceValidation;

class LocaleResourceController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected LocaleResourceRepository $localeResources,
        protected AuthorizationService $auth,
        protected CacheManager $cache
    ) {}

    public function List(int $page = 1, int $size = 10, ?string $search = null, ?int $languageId = null)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $search = trim($search ?? '');
        $where = [];
        if ($languageId !== null) {
            $where[] = [LocaleResourceEntityMap::PROPERTY_LanguageId, $languageId];
        }
        if ($search) {
            $search = strtolower($search);
            $parts = explode(' ', $search);
            foreach ($parts as $part) {
                if (trim($part)) {
                    $where[] = [
                        [LocaleResourceEntityMap::PROPERTY_Name, 'like', "%$part%"],
                        [LocaleResourceEntityMap::PROPERTY_Value, 'like', "%$part%"]
                    ];
                }
            }
        }
        $entities = $this->localeResources->search($where, [LocaleResourceEntityMap::PROPERTY_CreatedOn => 1], $page, $size);
        $models = array_map(fn($entity) => $this->mapper->map(LocaleResourceModel::class, $entity), $entities['list']);
        return ['list' => $models, 'total' => $entities['total']];
    }

    public function Get(int $id)
    {
        $entity = $this->localeResources->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        /**
         * @var LocaleResourceModel $model
         */
        $model = $this->mapper->map(LocaleResourceModel::class, $entity);
        return $model;
    }

    public function Update(int $id, LocaleResourceModel $resource)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new LocaleResourceValidation($resource))->getValidationRules();
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
        $localeResourceEntity = $this->localeResources->getById($id);
        if (!$localeResourceEntity) {
            return $this->NotFound();
        }
        /**
         * @var LocaleResourceEntity $localeResourceEntity
         */
        $localeResourceEntity = $this->mapper->map(LocaleResourceEntity::class, $resource, $localeResourceEntity);
        $success = $this->localeResources->update($localeResourceEntity);
        $model = null;
        if ($success) {
            /**
             * @var LocaleResourceModel $model
             */
            $model = $this->mapper->map(LocaleResourceModel::class, $localeResourceEntity);
            $cacheKey = sprintf(LocaleResourceEntityMap::CACHE_KEY, $localeResourceEntity->LanguageId);
            $this->cache->delete($cacheKey);
        }
        return $model;
    }

    public function Delete(int $id)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        /**
         * @var LocaleResourceEntity $localeResourceEntity
         */
        $LocaleResourceEntity = $this->localeResources->getById($id);
        if (!$LocaleResourceEntity) {
            return $this->NotFound();
        }
        $success = $this->localeResources->delete($LocaleResourceEntity);
        $cacheKey = sprintf(LocaleResourceEntityMap::CACHE_KEY, $localeResourceEntity->LanguageId);
        $this->cache->delete($cacheKey);
        return $success;
    }

    public function Create(LocaleResourceModel $resource)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new LocaleResourceValidation($resource))->getValidationRules();
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
         * @var LocaleResourceEntity $localeResourceEntity
         */
        $localeResourceEntity = $this->mapper->map(LocaleResourceEntity::class, $resource);
        $success = $this->localeResources->create($localeResourceEntity);
        $cacheKey = sprintf(LocaleResourceEntityMap::CACHE_KEY, $localeResourceEntity->LanguageId);
        $this->cache->delete($cacheKey);
        return $success ? $this->mapper->map(LocaleResourceModel::class, $localeResourceEntity) : null;
    }
}
