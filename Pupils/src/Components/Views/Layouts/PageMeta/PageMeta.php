<?php

namespace Pupils\Components\Views\Layouts\PageMeta;

use Exception;
use Pupils\Components\Services\Layouts\PageMetaService;
use Viewi\Components\BaseComponent;
use Viewi\Components\Lifecycle\OnMounted;

class PageMeta extends BaseComponent implements OnMounted
{
    public string $title = '';
    public ?string $description = null;
    public ?string $keywords = null;
    public ?string $image = null;

    public function __construct(private PageMetaService $meta) {}

    public function mounted()
    {
        throw new Exception("Under discussion, please do not use it for now.");
        // under discussion
        $callback = function ($name) {
            return function () use ($name) {
                $this->meta->setValue($name, $this->{$name});
            };
        };
        foreach ($this->_props as $name => $value) {
            $this->meta->setValue($name, $this->{$name});
            $this->watch($name, ($callback)($name));
        }
    }
}
