<?php

namespace FluffyPaws\Migrations\Localization;

class LocaleEnglishMigration02 extends LocaleEnglishMigration
{
    public function getResources(): array
    {
        return [
            'email.layout.title' => 'Paws',
            'email.layout.hero' => 'Fluffy paws. Treats included.',
            'email.layout.all-rights-reserved' => 'All rights reserved.',
            'email.activate.title' => 'Please confirm your email',
            'email.activate.please-confirm' => 'Hello, {name}, please confirm your email address.',
            'email.activate.click-here-to-activate' => 'Click here to confirm.',
            'email.reset-password.title' => 'Reset password',
            'email.activate.message' => 'Hello, {name}, please click this link to reset your password:',
            'email.reset-password.click-here' => 'Click here to reset.',
            'email-verification.title' => 'Confirm your email',
            'email-verification.failed' => 'Could not confirm email address, perhaps activation code has expired.',
            'email-verification.success' => 'Email address has been confirmed.',
        ];
    }
}
