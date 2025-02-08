<?php

namespace Pupils\Components\Views\Admin\Menu;

use Pupils\Components\Models\Menu\MenuItem;
use Pupils\Components\Services\Layouts\LayoutService;
use Viewi\Components\BaseComponent;
use Viewi\Components\Callbacks\Subscription;
use Viewi\Components\Routing\ClientRoute;

class AdminMenu extends BaseComponent
{
    public string $activeLink = '/';
    private Subscription $pathSubscription;
    public array $menuItems = [];

    public function __construct(private ClientRoute $route, private LayoutService $layout) {}

    public function init()
    {
        $this->pathSubscription = $this->route->urlWatcher()->subscribe(function (string $urlPath) {
            $this->activeLink = $urlPath;
            $this->layout->showMobileMenu = false;
        });
        $this->menuItems = [
            new MenuItem('Dashboard', '/admin', 'bi-speedometer2'),
            new MenuItem('Pages', '/admin/content', 'bi-grid', ['/admin/content/create'], "#^/admin/content/(.*)$#i"),
            new MenuItem('Menu items', null, 'bi-menu-button-wide', [], null, [
                new MenuItem('Header menu', '/admin/menu/header', null, ['/admin/menu/header/create'], "#^/admin/menu/header/(.*)$#i"),
                new MenuItem('Footer menu', '/admin/menu/footer', null, ['/admin/menu/footer/create'], "#^/admin/menu/footer/(.*)$#i")
            ]),
            new MenuItem('Blog', '/admin/blog', 'bi-pencil', ['/admin/blog/create'], "#^/admin/blog/(.*)$#i"),
            new MenuItem('Users', '/admin/user', 'bi-people-fill', ['/admin/users/create'], "#^/admin/users/(.*)$#i"),
            new MenuItem('Email templates', '/admin/email-templates', 'bi-envelope-paper', ['/admin/email-templates/create'], "#^/admin/email-templates/(.*)$#i"),
            new MenuItem('Localization', '/admin/language', 'bi-translate', ['/admin/language/create'], "#^/admin/language/(.*)$#i"),


            // Products #grid
            // Customers #people-circle
            // Home #home
            new MenuItem('Home', '/', 'bi-house-door'),
        ];
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
