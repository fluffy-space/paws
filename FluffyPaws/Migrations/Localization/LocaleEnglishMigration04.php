<?php

namespace FluffyPaws\Migrations\Localization;

class LocaleEnglishMigration04 extends LocaleEnglishMigration
{
    public function getResources(): array
    {
        return [
            'login.validation.email-required' => 'Email is required',
        ];
    }
}
