<?php

namespace SharedPaws\Models\MenuItem;

use SharedPaws\Validation\IValidationRules;
use SharedPaws\Validation\ValidationRules;

class MenuItemValidation implements IValidationRules
{
    public function __construct(private MenuItemModel $page) {}

    public function getValidationRules(): array
    {
        return ValidationRules::rules($this->page)
            ->maxLength('Link', 400)
            ->toList();
    }
}
