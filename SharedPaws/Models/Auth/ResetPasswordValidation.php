<?php

namespace SharedPaws\Models\Auth;

use SharedPaws\Validation\ValidationRules;

class ResetPasswordValidation
{
    public function __construct(private ResetPasswordModel $model, private $localize)
    {
    }

    public function getValidationRules()
    {
        return ValidationRules::rules($this->model)
            ->required('Password', ($this->localize)('login.validation.password-required'))
            ->required('PasswordConfirmation', ($this->localize)('register.validation.password-confirmation-required'))
            ->match('PasswordConfirmation', 'Password', ($this->localize)('register.validation.password-confirmation-match'))
            ->toList();
    }
}
