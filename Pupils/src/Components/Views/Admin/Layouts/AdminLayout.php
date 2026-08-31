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
    /**
     * Drop the container-xl max-width for pages that need the whole viewport
     * (log tables, wide grids): <AdminLayout title="$title" fullWidth>.
     * container-fluid stays on so the horizontal gutter is unchanged.
     */
    public bool $fullWidth = false;
    public bool $menuActive = false;
    public string $currentPath = '/';

    public function __construct(public LayoutService $layout, ConfigService $config)
    {
        $this->assetsUrl = $config->get('assetsUrl');
    }

    public function mounting()
    {
        // The layout instance is REUSED across client-side navigation, and only the props the
        // incoming page actually passes are re-applied — anything it omits keeps the previous
        // page's value. So every prop a page may leave out has to be reset here, before the new
        // props land, exactly as resetMeta() does for the meta tags.
        $this->fullWidth = false;
        $this->resetMeta();
    }

    public function mounted()
    {
        $this->menuActive = false;
        $this->mountMetaTags();
    }
}
