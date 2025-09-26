<?php

namespace Pupils\Components\Views\Admin\Layouts;

use Pupils\Components\Services\Layouts\LayoutService;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;

class AdminLayout extends BaseComponent
{
    public string $title = 'Fluffy';
    public string $assetsUrl = '/';
    public bool $menuActive = false;
    public string $currentPath = '/';

    public function __construct(public LayoutService $layout, ConfigService $config)
    {
        $this->assetsUrl = $config->get('assetsUrl');
    }

    public function mounted()
    {
        $this->menuActive = false;
    }
}
