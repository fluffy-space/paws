<?php

namespace Pupils\Components\Views\Admin\Blog;

use Pupils\Components\Guards\ManageBlogGuard;
use Pupils\Components\Views\Admin\EditPage\EditPage;
use SharedPaws\Models\BaseModel;
use SharedPaws\Models\Blog\BlogPostModel;
use SharedPaws\Models\Blog\BlogValidation;
use SharedPaws\Validation\IValidationRules;
use Viewi\Components\Attributes\Middleware;

#[Middleware([ManageBlogGuard::class])]
class BlogEdit extends EditPage
{
    public string $segment = 'blog';
    public string $name = "Blog post";

    public function getValidation(BaseModel $item): ?IValidationRules
    {
        return new BlogValidation($item);
    }

    public function getNewItem(): BaseModel
    {
        return new BlogPostModel();
    }
}
