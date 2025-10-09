<?php

namespace Pupils\Components\Services\Layouts;

use Pupils\Components\Views\Layouts\HasMetaTags;
use Viewi\DI\Singleton;

#[Singleton]
class PageMetaService
{
    use HasMetaTags;

    public function setValue(string $name, $value)
    {
        $this->{$name} = $value;
    }
}
