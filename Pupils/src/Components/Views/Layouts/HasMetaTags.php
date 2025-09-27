<?php

namespace Pupils\Components\Views\Layouts;

trait HasMetaTags
{
    public string $title = '';
    public ?string $description = null;
    public ?string $keywords = null;
    public ?string $image = null;
    public ?string $metaType = null;
    public array $tags = ['title', 'description', 'keywords', 'image', 'metaType'];
}
