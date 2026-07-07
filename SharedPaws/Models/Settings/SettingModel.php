<?php

namespace SharedPaws\Models\Settings;

use SharedPaws\Models\BaseModel;

/**
 * A settings row for the admin list. Uniform structure for every setting; the
 * Value is edited inline (as its stored text form) with a Type-appropriate
 * editor. OptionsList + codeDefined are read-only render/annotation helpers.
 */
class SettingModel extends BaseModel
{
    public string $Key = '';
    public ?string $Value = null;
    public string $Type = 'string';
    /** JSON string of allowed choices (dropdown/multiselect). */
    public ?string $Options = null;
    public ?string $Group = null;
    public ?string $Label = null;
    public ?string $Description = null;

    /** Server-decoded choices for the dropdown editor (read-only). */
    public array $OptionsList = [];
    /** True when declared in code (structure locked, cannot be deleted). */
    public bool $codeDefined = false;
}
