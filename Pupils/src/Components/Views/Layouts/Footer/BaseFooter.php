<?php

namespace Pupils\Components\Views\Layouts\Footer;

use SharedPaws\Models\MenuItem\MenuItemLocation;
use SharedPaws\Models\MenuItem\MenuItemModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;
use Viewi\Components\Http\HttpClient;

class BaseFooter extends BaseComponent
{
    public array $menuItems = [];

    /**
     * First year the site was live, from the app's publicConfig ('startYear'). The framework has
     * no business asserting a founding year on an app's behalf, so 0 (the default when the key is
     * absent) means "unknown" and the notice shows the current year alone — which is correct for
     * any new site. Set it in publicConfig.php.template to get a range.
     */
    public int $startYear = 0;

    /** Rendered copyright notice — built here rather than in the template, which cannot call date('Y') with an argument. */
    public string $copyright = '';

    public function __construct(private HttpClient $http, ConfigService $config)
    {
        $configured = $config->get('startYear');
        $this->startYear = $configured !== null ? (int) $configured : 0;
    }

    public function init()
    {
        $currentYear = (int) date('Y');
        $this->copyright = $this->startYear > 0 && $this->startYear < $currentYear
            ? ('© ' . $this->startYear . '–' . $currentYear)
            : ('© ' . $currentYear);

        $location = (new MenuItemLocation())->getLocationId('footer');
        $this->http->get("/api/menu/$location")->then(function (array $menuItems) {
            $columns = [];
            /** @var MenuItemModel[]  $menuItems  **/
            foreach ($menuItems as $item) {
                if (!isset($columns[$item->Column])) {
                    $columns[$item->Column] = [];
                }
                $columns[$item->Column][] = $item;
            }

            $this->menuItems = $columns;
        }, function () {
            // error
        });
    }
}
