<?php

namespace SharedPaws\Models\Auth;

use SharedPaws\Validation\ValidationRules;

class RegisterValidation
{
    public function __construct(private RegisterModel $model, private $localize)
    {
    }

    public function getValidationRules()
    {
        return ValidationRules::rules($this->model)
            ->required('FirstName', ($this->localize)('register.validation.first-name-required'))
            ->required('LastName', ($this->localize)('register.validation.last-name-required'))
            ->requiredAny('Email', 'Phone', ($this->localize)('login.validation.email-or-phone-required'))
            ->email('Email', ($this->localize)('register.validation.wrong-email'))
            ->phone('Phone', ($this->localize)('register.validation.wrong-phone'))
            ->required('Password', ($this->localize)('login.validation.password-required'))
            ->required('PasswordConfirmation', ($this->localize)('register.validation.password-confirmation-required'))
            ->match('PasswordConfirmation', 'Password', ($this->localize)('register.validation.password-confirmation-match'))
            ->toList();
    }
}
