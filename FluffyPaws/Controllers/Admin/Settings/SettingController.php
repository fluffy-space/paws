<?php

namespace FluffyPaws\Controllers\Admin\Settings;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Entities\Settings\SettingEntity;
use Fluffy\Data\Entities\Settings\SettingEntityMap;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Repositories\SettingRepository;
use Fluffy\Services\Auth\AuthorizationService;
use Fluffy\Services\Settings\SettingsService;
use FluffyPaws\Security\PawsCapability;
use SharedPaws\Models\Settings\SettingModel;

/**
 * Standard admin CRUD for the runtime settings store, gated by ManageSettings
 * (SuperAdmin-only). Uniform structure — the value is edited inline. Code-
 * declared settings are materialized as rows by SettingsService::ensureSeeded()
 * so they list/edit like any other; their structure (Key/Type/Options) is locked
 * and they cannot be deleted (they'd re-seed anyway). Admin-created "dynamic"
 * settings are fully editable/deletable.
 */
class SettingController extends BaseController
{
    public function __construct(
        protected IMapper $mapper,
        protected SettingRepository $settings,
        protected SettingsService $service,
        protected AuthorizationService $auth,
    ) {}

    public function List(int $page = 1, int $size = 10, ?string $search = null)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageSettings)) {
            return $this->Forbidden();
        }
        $this->service->ensureSeeded();
        $where = [];
        $search = trim($search ?? '');
        if ($search) {
            $where[] = [
                [SettingEntityMap::PROPERTY_Key, 'like', "%$search%"],
                [SettingEntityMap::PROPERTY_Group, 'like', "%$search%"],
                [SettingEntityMap::PROPERTY_Label, 'like', "%$search%"],
            ];
        }
        $result = $this->settings->search(
            $where,
            [SettingEntityMap::PROPERTY_Group => 1, SettingEntityMap::PROPERTY_Key => 1],
            $page,
            $size
        );
        return [
            'list' => array_map(fn($entity) => $this->toModel($entity), $result['list']),
            'total' => $result['total'],
        ];
    }

    public function Get(int $id)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageSettings)) {
            return $this->Forbidden();
        }
        /** @var SettingEntity $entity */
        $entity = $this->settings->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        return $this->toModel($entity);
    }

    public function Create(SettingModel $setting)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageSettings)) {
            return $this->Forbidden();
        }
        $setting->Key = strtolower(trim($setting->Key));
        $errors = $this->validate($setting, true, null);
        if (count($errors) > 0) {
            return $this->BadRequest($errors);
        }
        $entity = new SettingEntity();
        $this->apply($entity, $setting);
        $this->service->persist($entity);
        return $this->toModel($entity);
    }

    public function Update(int $id, SettingModel $setting)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageSettings)) {
            return $this->Forbidden();
        }
        /** @var SettingEntity $entity */
        $entity = $this->settings->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        $errors = $this->validate($setting, false, $entity);
        if (count($errors) > 0) {
            return $this->BadRequest($errors);
        }
        if ($this->service->isDefined($entity->Key)) {
            // Structure locked for code-declared settings — only the value + cosmetics change.
            $entity->Value = $setting->Value;
            $entity->Group = $this->nullable($setting->Group);
            $entity->Label = $this->nullable($setting->Label);
            $entity->Description = $this->nullable($setting->Description);
        } else {
            $this->apply($entity, $setting);
        }
        $this->service->persist($entity);
        return $this->toModel($entity);
    }

    public function Delete(int $id)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageSettings)) {
            return $this->Forbidden();
        }
        /** @var SettingEntity $entity */
        $entity = $this->settings->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        if ($this->service->isDefined($entity->Key)) {
            return $this->BadRequest(['Code-defined settings cannot be deleted.']);
        }
        $this->service->delete($entity->Key);
        return true;
    }

    /** @return string[] errors */
    private function validate(SettingModel $model, bool $create, ?SettingEntity $existing): array
    {
        $errors = [];
        $codeDefined = $existing !== null && $this->service->isDefined($existing->Key);
        // Type/options are locked for code-declared rows; otherwise they come from the model.
        $type = $codeDefined ? $existing->Type : $model->Type;
        $options = $codeDefined ? $this->service->optionsOf($existing->Key) : $this->decodeOptions($model->Options);

        if ($create) {
            if ($model->Key === '' || !preg_match('/^[a-z0-9]+([._-][a-z0-9]+)*$/', $model->Key)) {
                $errors[] = 'Key must be a dotted slug (lowercase letters, digits, dots/dashes).';
            } elseif ($this->service->has($model->Key)) {
                $errors[] = "A setting with key '{$model->Key}' already exists.";
            }
            if (!in_array($model->Type, SettingsService::TYPES, true)) {
                $errors[] = 'Unknown value type.';
            }
        }
        if (($type === 'dropdown' || $type === 'multiselect') && !$options) {
            $errors[] = 'Dropdown/multiselect settings require an Options list.';
        }
        $valueError = $this->service->validateValue($model->Value, $type, $options);
        if ($valueError !== null) {
            $errors[] = $valueError;
        }
        return $errors;
    }

    private function apply(SettingEntity $entity, SettingModel $model): void
    {
        $entity->Key = $model->Key;
        $entity->Type = $model->Type;
        $entity->Value = $model->Value;
        $options = $this->decodeOptions($model->Options);
        $entity->Options = $options !== null ? json_encode($options) : null;
        $entity->Group = $this->nullable($model->Group);
        $entity->Label = $this->nullable($model->Label);
        $entity->Description = $this->nullable($model->Description);
    }

    private function toModel(SettingEntity $entity): SettingModel
    {
        /** @var SettingModel $model */
        $model = $this->mapper->map(SettingModel::class, $entity);
        $model->OptionsList = $this->decodeOptions($entity->Options) ?? [];
        $model->codeDefined = $this->service->isDefined($entity->Key);
        return $model;
    }

    private function decodeOptions(?string $raw): ?array
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function nullable(?string $value): ?string
    {
        $value = $value === null ? '' : trim($value);
        return $value === '' ? null : $value;
    }
}
