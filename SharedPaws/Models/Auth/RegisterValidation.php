<?php

namespace SharedPaws\Models\Auth;

use SharedPaws\Validation\ValidationRules;

class RegisterValidation
{
    public function __construct(private RegisterModel $model, private $localize) {}

    /**
     * Which fields a registration form insists on.
     *
     * Defaults match what the form has always asked for, so an app that upgrades Paws sees no
     * change. An app that trims the form must pass `false` for whatever it stopped rendering —
     * otherwise the submission fails validation on fields nobody can see. Urlicer drives both
     * flags from the `registerAskName` / `registerAskPasswordConfirmation` config keys; the
     * Register component reads them for the browser and AuthorizationController for the server,
     * so the two sides cannot disagree.
     *
     * `FirstName`/`LastName` are nullable in the schema and editable later on the account page,
     * so nothing downstream needs them at signup — but a display site must then use
     * {@see \SharedPaws\Support\UserDisplay::forUser()} rather than concatenating the two,
     * which renders a bare space for an account that has neither.
     */
    public function getValidationRules(bool $requireEmail = true, bool $requireName = true, bool $confirmPassword = true)
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
