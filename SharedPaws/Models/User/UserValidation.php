<?php

namespace SharedPaws\Models\User;

use SharedPaws\Validation\IValidationRules;
use SharedPaws\Validation\ValidationRules;

class UserValidation implements IValidationRules
{
    public function __construct(private UserModel $user) {}

    public function getValidationRules(): array
    {
        return ValidationRules::rules($this->user)
            ->requiredAtLeast('Email', 'Phone')
            ->maxLength('Email', 255)
            ->maxLength('Phone', 255)
            ->match('NewPassword', 'ConfirmPassword', 'Both passwords should match.')
            ->toList();
    }
}
