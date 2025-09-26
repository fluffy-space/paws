<?php

namespace Pupils\Components\Views\Layouts;

use Pupils\Components\Services\Layouts\PageMetaService;
use Viewi\DI\Inject;
use Viewi\DI\Scope;

trait ManagesMetaTags
{
    use HasMetaTags;

    #[Inject(Scope::SINGLETON)]
    public PageMetaService $meta;

    public function mountMetaTags()
    {
        $callback = function ($name) {
            return function () use ($name) {
                $this->meta->setValue($name, $this->{$name});
            };
        };
        foreach ($this->tags as $name) {
            $this->meta->setValue($name, $this->{$name});
            $this->watch($name, ($callback)($name));
        }
    }
}
