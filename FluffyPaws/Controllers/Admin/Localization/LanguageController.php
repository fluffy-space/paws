<?php

namespace FluffyPaws\Controllers\Admin\Localization;

use FluffyPaws\Data\Entities\Localization\LanguageEntity;
use FluffyPaws\Data\Entities\Localization\LanguageEntityMap;
use FluffyPaws\Data\Repositories\LanguageRepository;
use Fluffy\Controllers\BaseController;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Services\Auth\AuthorizationService;
use SharedPaws\Models\Localization\LanguageModel;
use SharedPaws\Models\Localization\LanguageValidation;

class LanguageController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected LanguageRepository $languages,
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
            $where = [[LanguageEntityMap::PROPERTY_Name, 'like', "%$search%"]];
        }
        $entities = $this->languages->search($where, [LanguageEntityMap::PROPERTY_CreatedOn => 1], $page, $size);
        $models = array_map(fn($entity) => $this->mapper->map(LanguageModel::class, $entity), $entities['list']);
        return ['list' => $models, 'total' => $entities['total']];
    }

    public function Get(int $id)
    {
        $entity = $this->languages->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        /**
         * @var LanguageModel $model
         */
        $model = $this->mapper->map(LanguageModel::class, $entity);
        return $model;
    }

    public function Update(int $id, LanguageModel $language)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new LanguageValidation($language))->getValidationRules();
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
        $languageEntity = $this->languages->getById($id);
        if (!$languageEntity) {
            return $this->NotFound();
        }
        $languageEntity = $this->mapper->map(LanguageEntity::class, $language, $languageEntity);
        $success = $this->languages->update($languageEntity);
        $model = null;
        if ($success) {
            /**
             * @var LanguageModel $model
             */
            $model = $this->mapper->map(LanguageModel::class, $languageEntity);
        }
        return $model;
    }

    public function Delete(int $id)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $LanguageEntity = $this->languages->getById($id);
        if (!$LanguageEntity) {
            return $this->NotFound();
        }
        $success = $this->languages->delete($LanguageEntity);
        return $success;
    }

    public function Create(LanguageModel $language)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new LanguageValidation($language))->getValidationRules();
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

        $languageEntity = $this->mapper->map(LanguageEntity::class, $language);
        $success = $this->languages->create($languageEntity);
        return $success ? $this->mapper->map(LanguageModel::class, $languageEntity) : null;
    }
}
