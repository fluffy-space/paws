<?php

namespace Pupils\Components\Views\Admin\Localization;

use Pupils\Components\Guards\AdminGuard;
use Viewi\Components\Attributes\Middleware;
use Pupils\Components\Views\Admin\EditPage\EditPage;
use SharedPaws\Models\BaseModel;
use SharedPaws\Models\Localization\LanguageModel;
use SharedPaws\Models\Localization\LanguageValidation;
use SharedPaws\Validation\IValidationRules;

/**
 * 
 * @package Pupils\Components\Views\Admin\Localization
 * @property LanguageModel $item
 */
#[Middleware([AdminGuard::class])]
class LanguageEdit extends EditPage
{
    public string $segment = 'language';
    public bool $changePassword = false;
    public string $name = "Language";

    public function getValidation(BaseModel $item): ?IValidationRules
    {
        return new LanguageValidation($item);
    }

    public function getNewItem(): BaseModel
    {
        return new LanguageModel();
    }
}
