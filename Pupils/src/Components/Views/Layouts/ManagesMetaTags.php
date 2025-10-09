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

    public function resetMeta()
    {
        foreach ($this->tags as $name) {
            $this->{$name} = '';
            $this->meta->setValue($name, $this->{$name});
        }
    }

    public function mountMetaTags()
    {
        foreach ($this->tags as $name) {
            if ($this->{$name}) {
                $this->meta->setValue($name, $this->{$name});
            }
        }
    }

    // for top root component only
    public function watchMeta()
    {
        $callback = function ($name) {
            return function () use ($name) {
                $this->meta->setValue($name, $this->{$name});
            };
        };
        foreach ($this->tags as $name) {
            $this->watch($name, ($callback)($name));
        }
    }
}
