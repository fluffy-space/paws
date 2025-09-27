<?php

namespace FluffyPaws\Migrations\Localization;

class LocaleEnglishMigration05 extends LocaleEnglishMigration
{
    public function getResources(): array
    {
        return [
            'meta.twitter.site' => '@viewiphp',
            'meta.twitter.creator' => '@viewiphp',
        ];
    }
}
