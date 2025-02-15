<?php

namespace FluffyPaws\Migrations\Localization;

class LocaleEnglishMigration03 extends LocaleEnglishMigration
{
    public function getResources(): array
    {
        return [
            'blog.list.title' => 'Our blog',
            'blog.post.author' => 'Mr. Fluffy',
            'blog.post.min-to-read' => '{min} min to read',
            'blog.list.meta-description' => 'Latest new and articles.',
            'blog.list.meta-keywords' => 'Blog articles',
        ];
    }
}
