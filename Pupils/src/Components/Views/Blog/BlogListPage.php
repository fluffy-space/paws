<?php

namespace Pupils\Components\Views\Blog;

use SharedPaws\Models\Blog\BlogPostModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Routing\ClientRoute;
use Viewi\UI\Components\Pagination\PaginationModel;

class BlogListPage extends BaseComponent
{
    /**
     * 
     * @var BlogPostModel[]
     */
    public array $posts = [];
    public PaginationModel $pagination;

    public function __construct(private HttpClient $http, private ClientRoute $route)
    {
        $this->pagination = new PaginationModel(1, 12, 1);
    }

    public function init()
    {
        $this->pagination->setPage($this->route->getQueryParams()['page'] ?? 1);
        $this->http->get("/api/blog?size=12&page={$this->pagination->page}")
            ->then(function (array $posts) {
                $this->posts = $posts['list'];
                $this->pagination->setTotal($posts['total']);
            }, function () {
                // error
            });
    }

    public function postDate(int $milliseconds)
    {
        $seconds = $milliseconds / 1000000;
        return gmdate('d.m.Y', (int)$seconds); //  H:i:s
    }
}
