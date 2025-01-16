<?php

namespace SharedPaws\Models\Localization;

use SharedPaws\Validation\ValidationRules;

class LanguageValidation
{
    public function __construct(private LanguageModel $model)
    {
    }

    public function getValidationRules()
    {
        return ValidationRules::rules($this->model)
            ->required('Name')
            ->maxLength('Name', 100)
            ->required('LanguageCulture')
            ->maxLength('LanguageCulture', 20)
            ->required('SeoCode')
            ->maxLength('SeoCode', 2)
            ->toList();
    }
}
