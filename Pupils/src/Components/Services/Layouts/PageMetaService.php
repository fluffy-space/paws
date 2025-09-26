<?php

namespace Pupils\Components\Services\Layouts;

use Pupils\Components\Views\Layouts\HasMetaTags;
use Viewi\Components\IStartUp\IStartUp;
use Viewi\Components\Routing\ClientRoute;
use Viewi\DI\Inject;
use Viewi\DI\Scope;
use Viewi\DI\Singleton;

#[Singleton]
class PageMetaService implements IStartUp
{
    use HasMetaTags;

    #[Inject(Scope::SINGLETON)]
    public ClientRoute $route;

    public function setUp()
    {
        $this->route->urlWatcher()
            ->subscribe(function () {
                foreach ($this->tags as $name) {
                    $this->{$name} = '';
                    $this->setValue($name, $this->{$name});
                }
            });
    }

    public function setValue(string $name, $value)
    {
        $this->{$name} = $value;
    }
}
