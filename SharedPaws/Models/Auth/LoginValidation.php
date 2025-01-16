<?php

namespace SharedPaws\Models\Auth;

use SharedPaws\Validation\ValidationRules;

class LoginValidation
{
    public function __construct(private LoginModel $model, private $localize)
    {
    }

    public function getValidationRules()
    {
        return ValidationRules::rules($this->model)
            ->required('Email', ($this->localize)('login.validation.email-or-phone-required'))
            ->emailOrPhone(
                'Email',
                ($this->localize)('login.validation.wrong-email'),
                ($this->localize)('login.validation.wrong-phone')
            )
            ->required('Password', ($this->localize)('login.validation.password-required'))
            ->toList();
    }
}
