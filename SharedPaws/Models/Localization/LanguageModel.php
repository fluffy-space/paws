<?php

namespace SharedPaws\Models\Localization;

use SharedPaws\Models\BaseModel;

class LanguageModel extends BaseModel
{
    public string $Name = '';
    public string $LanguageCulture = '';
    public string $SeoCode = '';
    public bool $Rtl = false;
    public bool $Published = false;
    public int $DisplayOrder = 0;
}
