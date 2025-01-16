<?php

namespace SharedPaws\Models\Localization;

use SharedPaws\Validation\ValidationRules;

class LocaleResourceValidation
{
    public function __construct(private LocaleResourceModel $model)
    {
    }

    public function getValidationRules()
    {
        return ValidationRules::rules($this->model)
            ->required('Name')
            ->maxLength('Name', 200)
            ->required('Value')
            ->toList();
    }
}
