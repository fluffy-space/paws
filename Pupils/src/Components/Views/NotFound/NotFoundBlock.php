<?php


namespace Pupils\Components\Views\NotFound;

use SharedPaws\Models\Content\PageModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;

class NotFoundBlock extends BaseComponent
{
    public ?PageModel $page = null;
    public bool $ready = false;

    public function __construct(
        private HttpClient $http
    ) {}

    public function init()
    {
        $this->http->get("/api/content?path=404")
            ->then(function (?PageModel $page) {
                $this->page = $page;
            }, function () {}, function() {
                $this->ready = true;
            });
    }
}
