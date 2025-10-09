<?php

namespace Pupils\Components\Views\Admin\Layouts;

use Pupils\Components\Services\Layouts\LayoutService;
use Pupils\Components\Views\Layouts\ManagesMetaTags;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;
use Viewi\Components\Lifecycle\OnMounting;

class AdminLayout extends BaseComponent implements OnMounting
{
    use ManagesMetaTags;
    public string $assetsUrl = '/';
    public bool $menuActive = false;
    public string $currentPath = '/';

    public function __construct(public LayoutService $layout, ConfigService $config)
    {
        $this->assetsUrl = $config->get('assetsUrl');
    }

    public function mounting()
    {
        $this->resetMeta();
    }

    public function mounted()
    {
        $this->menuActive = false;
        $this->mountMetaTags();
    }
}
