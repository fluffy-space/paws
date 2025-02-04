<?php

namespace SharedPaws\Models\Content;

use SharedPaws\Validation\ValidationRules;

class PageValidation
{
    public function __construct(private PageModel $page) {}

    public function getValidationRules()
    {
        return ValidationRules::rules($this->page)
            //->required('Title')
            ->maxLength('Slug', 400)
            ->required('Body')
            ->maxLength('MetaKeywords', 400)
            ->maxLength('MetaTitle', 400)
            ->maxLength('MetaDescription', 400)
            ->toList();
    }
}
