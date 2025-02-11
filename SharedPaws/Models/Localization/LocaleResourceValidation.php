<?php

namespace SharedPaws\Models\Localization;

use SharedPaws\Validation\IValidationRules;
use SharedPaws\Validation\ValidationRules;

class LocaleResourceValidation implements IValidationRules
{
    public function __construct(private LocaleResourceModel $model) {}

    public function getValidationRules(): array
    {
        return ValidationRules::rules($this->model)
            ->required('Name')
            ->maxLength('Name', 200)
            ->required('Value')
            ->toList();
    }
}
