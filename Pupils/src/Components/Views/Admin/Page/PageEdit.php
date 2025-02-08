<?php

namespace Pupils\Components\Views\Admin\Page;

use Pupils\Components\Guards\AdminGuard;
use Pupils\Components\Views\Admin\EditPage\EditPage;
use SharedPaws\Models\BaseModel;
use SharedPaws\Models\Content\PageModel;
use SharedPaws\Models\Content\PageValidation;
use SharedPaws\Validation\IValidationRules;
use Viewi\Components\Attributes\Middleware;

#[Middleware([AdminGuard::class])]
class PageEdit extends EditPage
{
    public string $segment = 'content';
    public string $name = "Page";

    public function getValidation(BaseModel $item): ?IValidationRules
    {
        return new PageValidation($item);
    }

    public function getNewItem(): BaseModel
    {
        return new PageModel();
    }
}
