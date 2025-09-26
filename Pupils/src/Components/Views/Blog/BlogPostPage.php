<?php

namespace Pupils\Components\Views\Blog;

use Pupils\Components\Services\Localization\HasLocalization;
use SharedPaws\Models\Blog\BlogPostModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\HtmlNode;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\Components\Routing\ClientRoute;

class BlogPostPage extends BaseComponent
{
    use HasLocalization;
    public string $title = 'Loading..';
    public ?BlogPostModel $post = null;
    public bool $notFound = false;
    public ?HtmlNode $body = null;
    /**
     * 
     * @var null|array{Slug: string, Title: string}
     */
    public ?array $nextPost = null;
    /**
     * 
     * @var null|array{Slug: string, Title: string}
     */
    public ?array $previousPost = null;

    public function __construct(
        public string $seoName,
        private HttpClient $http,
        private ClientRoute $route
    ) {}

    public function init()
    {
        $this->http->get("/api/blog/{$this->seoName}?next=true")
            ->then(function (array $data) {
                $this->post = $data['post'];
                $this->title = $this->post->MetaTitle ? $this->post->MetaTitle : $this->post->Title;
                $this->nextPost = $data['next'];
                $this->previousPost = $data['previous'];
                $this->onBlogSet($data);
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

    public function postDate(int $milliseconds)
    {
        $seconds = $milliseconds / 1000000;
        return gmdate('d.m.Y', (int)$seconds); //  H:i:s
    }

    public function onBlogSet($response)
    {
        /** open to overrides */
    }
}
