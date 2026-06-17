<?php

namespace Pupils\Components\Views\Layouts\Header;

use Pupils\Components\Services\Auth\AuthService;
use Pupils\Components\Services\Layouts\LayoutService;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use SharedPaws\Models\MenuItem\MenuItemLocation;
use Viewi\Components\BaseComponent;
use Viewi\Components\Callbacks\Subscription;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\DOM\DomHelper;
use Viewi\Components\DOM\HtmlNode;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Routing\ClientRoute;

class BaseHeader extends BaseComponent
{
    public array $menuItems = [];

    public bool $showMenu = false;
    private Subscription $pathSubscription;
    private Subscription $sessionSubscription;
    public ?HtmlNode $area = null;
    public $onDocumentClick = null;
    public bool $isLoggedIn = false;

    public function __construct(
        private HttpClient $http,
        private ClientRoute $route,
        private DomHelper $dom,
        public LayoutService $layout,
        private AuthService $auth
    ) {}

    public function init()
    {
        $this->pathSubscription = $this->route->urlWatcher()->subscribe(function (string $urlPath) {
            $this->closeMenu();
        });
        $this->setUpOutsideClick();
        $location = (new MenuItemLocation())->getLocationId('header');
        $this->http->get("/api/menu/$location")->then(function (array $menuItems) {
            $this->menuItems = $menuItems;
        }, function () {
            // error
        });

        $this->sessionSubscription = $this->auth->subscribe(function (?UserAuthSessionModel $userSession) {
            $this->isLoggedIn = $userSession !== null && $userSession->isAuthenticated;
            <<<'javascript'
            console.log(userSession);
            javascript;
        });

        <<<'javascript'
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            $this.layout.dark = true;
        }
        javascript;
    }

    function menu()
    {
        $this->showMenu = !$this->showMenu;
    }

    function closeMenu()
    {
        $this->showMenu = false;
    }

    function setUpOutsideClick()
    {
        $document = $this->dom->getDocument();
        if ($document) {
            $this->onDocumentClick = function (DomEvent $event) {
                if (
                    $this->showMenu
                    && $this->area !== $event->target
                    && !$this->area->contains($event->target)
                ) {
                    // click is outside
                    $this->closeMenu();
                }
            };
            $document->addEventListener('click', $this->onDocumentClick);
        }
    }

    public function destroy()
    {
        $this->pathSubscription->unsubscribe();
        $this->dom->getDocument()?->removeEventListener('click', $this->onDocumentClick);
    }

    public function logout()
    {
        $this->http->post("/api/authorization/logout")->then(function () {
            $this->auth->reset();
        }, function () {
            // error
        });
    }
}
