<?php

namespace SharedPaws\Models\Blog;

use SharedPaws\Validation\IValidationRules;
use SharedPaws\Validation\ValidationRules;

class BlogValidation implements IValidationRules
{
    public function __construct(private BlogPostModel $post) {}

    public function getValidationRules(): array
    {
        return ValidationRules::rules($this->post)
            ->required('Title')
            ->maxLength('Slug', 400)
            ->required('Body')
            ->required('BodyOverview')
            ->maxLength('MetaKeywords', 400)
            ->maxLength('MetaTitle', 400)
            ->maxLength('MetaDescription', 400)
            ->toList();
    }
}
