<?php

namespace Pupils\Components\Services\Dashboard;

use Pupils\Components\Models\Menu\MenuItem;
use Viewi\DI\Singleton;

#[Singleton]
class AdminMenuService
{

    protected array $_menuItems = [];

    protected function getDefaultItems(): array
    {
        return [
            new MenuItem('Dashboard', 0, '/admin', 'bi-speedometer2'),
            new MenuItem('Pages', 1, '/admin/content', 'bi-grid', ['/admin/content/create'], "#^/admin/content/(.*)$#i"),
            new MenuItem('Menu items', 3, null, 'bi-menu-button-wide', [], null, [
                new MenuItem('Header menu', 4, '/admin/menu/header', null, ['/admin/menu/header/create'], "#^/admin/menu/header/(.*)$#i"),
                new MenuItem('Footer menu', 5, '/admin/menu/footer', null, ['/admin/menu/footer/create'], "#^/admin/menu/footer/(.*)$#i"),
                new MenuItem('Email menu', 6, '/admin/menu/email', null, ['/admin/menu/footer/email'], "#^/admin/menu/email/(.*)$#i")
            ]),
            new MenuItem('Blog', 7, '/admin/blog', 'bi-pencil', ['/admin/blog/create'], "#^/admin/blog/(.*)$#i"),
            new MenuItem('Users', 8, '/admin/user', 'bi-people-fill', ['/admin/users/create'], "#^/admin/users/(.*)$#i"),
            new MenuItem('Email templates', 9, '/admin/email-templates', 'bi-envelope-paper', ['/admin/email-templates/create'], "#^/admin/email-templates/(.*)$#i"),
            new MenuItem('Localization', 10, '/admin/language', 'bi-translate', ['/admin/language/create'], "#^/admin/language/(.*)$#i"),


            // Products #grid
            // Customers #people-circle
            // Home #home
            new MenuItem('Home', 100, '/', 'bi-house-door'),
        ];
    }

    public function getItems(): array
    {
        // TODO: order by $order
        $items = [...$this->getDefaultItems(), ...$this->_menuItems];
        usort($items, fn(MenuItem $a, MenuItem $b) => $a->order - $b->order);
        return $items;
    }

    public function addMenuItem(MenuItem $menuItem): void
    {
        $this->_menuItems[] = $menuItem;
    }
}
