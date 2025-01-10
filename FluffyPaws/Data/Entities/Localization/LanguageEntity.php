<?php

namespace FluffyPaws\Data\Entities\Localization;

use Fluffy\Data\Entities\BaseEntity;

class LanguageEntity extends BaseEntity
{
    public string $Name;
    public string $LanguageCulture;
    public string $SeoCode;
    public bool $Rtl = false;
    public bool $Published = false;
    public int $DisplayOrder = 0;
}
