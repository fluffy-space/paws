<?php

namespace SharedPaws\Models\Localization;

use SharedPaws\Models\BaseModel;

class LocaleResourceModel extends BaseModel
{
    public function __construct(public int $LanguageId = 0) {}
    public string $Name = '';
    public string $Value = '';
}
