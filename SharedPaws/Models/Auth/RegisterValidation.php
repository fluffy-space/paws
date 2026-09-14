<?php

namespace SharedPaws\Models\Auth;

use SharedPaws\Validation\ValidationRules;

class RegisterValidation
{
    public function __construct(private RegisterModel $model, private $localize) {}

    /**
     * Registration asks for an email and a password, and nothing else.
     *
     * A person's name is not needed to open an account, and the second password box is worse at
     * catching a typo than being able to read the first one — both were dropped from the form, so
     * requiring them here would fail a submission over fields nobody can see. They stay available
     * as opt-ins for an app that does render them; `FirstName`/`LastName` are nullable in the
     * schema and editable later on the account page, so nothing downstream needs them at signup.
     * Display sites must use {@see \SharedPaws\Support\UserDisplay::forUser()} rather than
     * concatenating the two fields, which renders a bare space for an account that has neither.
     */
    public function getValidationRules(bool $requireEmail = true, bool $requireName = false, bool $confirmPassword = false)
    {
        $rules = ValidationRules::rules($this->model)
            //->phone('Phone', ($this->localize)('register.validation.wrong-phone'))
            ->required('Password', ($this->localize)('login.validation.password-required'));
        if ($requireName) {
            $rules = $rules
                ->required('FirstName', ($this->localize)('register.validation.first-name-required'))
                ->required('LastName', ($this->localize)('register.validation.last-name-required'));
        }
        if ($confirmPassword) {
            $rules = $rules
                ->required('PasswordConfirmation', ($this->localize)('register.validation.password-confirmation-required'))
                ->match('PasswordConfirmation', 'Password', ($this->localize)('register.validation.password-confirmation-match'));
        }
        if ($requireEmail) {
            $rules = $rules
                ->required('Email', ($this->localize)('login.validation.email-required'))
                ->email('Email', ($this->localize)('register.validation.wrong-email'));
        }
        return $rules->toList();
    }
}
