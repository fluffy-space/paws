<?php

namespace SharedPaws\Models\Settings;

use SharedPaws\Validation\IValidationRules;
use SharedPaws\Validation\ValidationRules;

class SettingValidation implements IValidationRules
{
    public function __construct(private SettingModel $model) {}

    public function getValidationRules(): array
    {
        return ValidationRules::rules($this->model)
            ->required('Key')
            ->maxLength('Key', 255)
            ->toList();
    }
}
