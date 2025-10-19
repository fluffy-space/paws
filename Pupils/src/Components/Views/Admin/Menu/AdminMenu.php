<?php

namespace Pupils\Components\Views\Admin\Menu;

use Pupils\Components\Models\Menu\MenuItem;
use Pupils\Components\Services\Dashboard\AdminMenuService;
use Pupils\Components\Services\Layouts\LayoutService;
use Viewi\Components\BaseComponent;
use Viewi\Components\Callbacks\Subscription;
use Viewi\Components\Routing\ClientRoute;

class AdminMenu extends BaseComponent
{
    public string $activeLink = '/';
    private Subscription $pathSubscription;
    public array $menuItems = [];

    public function __construct(
        private ClientRoute $route,
        private LayoutService $layout,
        private AdminMenuService $menuService
    ) {}

    public function init()
    {
        $this->pathSubscription = $this->route->urlWatcher()->subscribe(function (string $urlPath) {
            $this->activeLink = $urlPath;
            $this->layout->showMobileMenu = false;
        });
        $this->menuItems = $this->menuService->getItems();
    }

    public function destroy()
    {
        $this->pathSubscription->unsubscribe();
    }

    public function isActive(MenuItem $menuItem)
    {
        $isActive = $this->activeLink === $menuItem->url
            || in_array($this->activeLink, $menuItem->alternatives)
            || ($menuItem->pattern !== null && preg_match($menuItem->pattern, $this->activeLink));
        if ($isActive && $menuItem->parent !== null && !$menuItem->parent->expanded) {
            $this->toggleItem($menuItem->parent);
        }
        return $isActive;
    }

    public function isExpanded(MenuItem $menuItem)
    {
        return $menuItem->expanded;
    }

    public function onMenuClick(MenuItem $menuItem)
    {
        if ($menuItem->children !== null) {
            // collapse menu
            $this->toggleItem($menuItem);
        }
    }

    public function toggleItem(MenuItem $menuItem)
    {
        $menuItem->expanded = !$menuItem->expanded;
    }
}
