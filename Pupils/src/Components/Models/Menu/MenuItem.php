<?php

namespace Pupils\Components\Models\Menu;

class MenuItem
{
    public bool $expanded = false;
    public ?MenuItem $parent = null;

    public function __construct(
        public string $title,
        public int $order = 0,
        public ?string $url = null,
        public ?string $icon = null,
        public array $alternatives = [],
        public ?string $pattern = null,
        public ?array $children = null
    ) {
        if ($this->children !== null) {
            for ($i = 0; $i < count($this->children); $i++) {
                $this->children[$i]->parent = $this;
            }
        }
    }
}
