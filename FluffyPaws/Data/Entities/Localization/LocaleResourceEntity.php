<?php

namespace FluffyPaws\Data\Entities\Localization;

use Fluffy\Data\Entities\BaseEntity;

class LocaleResourceEntity extends BaseEntity
{
    public int $LanguageId;
    public string $Name;
    public string $Value;
}
