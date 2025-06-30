<?php

namespace Pupils\Components\Views\Content;

use Pupils\Components\Services\Localization\HasLocalization;
use SharedPaws\Models\Content\PageModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\HtmlNode;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\Components\Lifecycle\OnInit;
use Viewi\Components\Routing\ClientRoute;

class ContentPage extends BaseComponent implements OnInit
{
    use HasLocalization;
    public string $title = 'Loading..';
    public ?PageModel $page = null;
    public bool $notFound = false;
    public ?HtmlNode $body = null;

    public function __construct(
        private HttpClient $http,
        private ClientRoute $route
    ) {}

    public function init()
    {
        $this->http->get("/api/content?path={$this->route->getUrlPath()}")
            ->then(function (?PageModel $page) {
                $this->page = $page;
                $this->title = $this->page->MetaTitle ? $this->page->MetaTitle : $this->page->Title;
            }, function (Response $response) {
                if ($response && $response->status) {
                    if ($response->status === 404) {
                        $this->notFound = true;
                        $this->title = $this->localization->t('layout.page-not-found');
                        $this->route->setResponseStatus($response->status);
                    }
                }
            });
    }
}
