<?php

namespace Pupils\Components\Emails\Layouts;

use SharedPaws\Models\MenuItem\MenuItemLocation;
use SharedPaws\Models\MenuItem\MenuItemModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;
use Viewi\Components\Http\HttpClient;

class EmailLayout extends BaseComponent
{
    public static bool $_noBrowser = true;

    public string $title = 'Email';
    public string $baseUrl = '/';

    /**
     * Footer links, from the MenuItem table at the 'email' location — DB-managed and
     * admin-editable like the site header/footer menus, rather than hard-coded here.
     * Links are copied into fresh models with an ABSOLUTE href: an email has no origin
     * to resolve "/help" against, and the source models are shared cache entries that
     * must not be mutated.
     * @var MenuItemModel[]
     */
    public array $footerLinks = [];

    /** if= cannot call a method with arguments, so the emptiness test is a property. */
    public bool $hasFooterLinks = false;

    public function __construct(private HttpClient $http, ConfigService $configService)
    {
        $this->baseUrl = $configService->get('baseUrl');
    }

    public function init()
    {
        $location = (new MenuItemLocation())->getLocationId('email');
        $this->http->get("/api/menu/$location")->then(function (array $menuItems) {
            $links = [];
            /** @var MenuItemModel[] $menuItems */
            foreach ($menuItems as $item) {
                if ($item->Link === null || $item->Link === '') {
                    // Column headings (link-less rows) make no sense in a one-line email footer.
                    continue;
                }
                $link = new MenuItemModel();
                $link->Title = $item->Title;
                $link->Link = $this->absoluteLink($item->Link);
                $links[] = $link;
            }
            $this->footerLinks = $links;
            $this->hasFooterLinks = count($links) > 0;
        }, function () {
            // No footer links rather than no email — the copyright line still renders.
        });
    }

    public function absoluteLink(string $link): string
    {
        if (
            str_starts_with($link, 'http://')
            || str_starts_with($link, 'https://')
            || str_starts_with($link, 'mailto:')
            || str_starts_with($link, 'tel:')
        ) {
            return $link;
        }
        return $this->baseUrl . $link;
    }
}
