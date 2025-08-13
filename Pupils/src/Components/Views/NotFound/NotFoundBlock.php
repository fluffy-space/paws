<?php


namespace Pupils\Components\Views\NotFound;

use SharedPaws\Models\Content\PageModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;
use Viewi\Components\Http\HttpClient;

class NotFoundBlock extends BaseComponent
{
    public ?PageModel $page = null;
    public bool $ready = false;
    public string $assetsBaseUrl = '';

    public function __construct(
        private HttpClient $http,
        private ConfigService $config
    ) {}

    public function init()
    {
        $this->assetsBaseUrl = $this->config->get('assetsUrl');
        $this->http->get("/api/content?path=404")
            ->then(function (?PageModel $page) {
                $this->page = $page;
            }, function () {}, function () {
                $this->ready = true;
            });
    }
}
