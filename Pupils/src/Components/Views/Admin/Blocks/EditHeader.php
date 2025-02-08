<?php

namespace Pupils\Components\Views\Admin\Blocks;

use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;

class EditHeader extends BaseComponent
{
    public bool $createMode = false;
    public int $state = 0;
    public ?string $backUrl = null;
    public ?string $title = null;

    public function onSave(DomEvent $event)
    {
        $this->emitEvent('click', $event);
    }
}
