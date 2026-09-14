<?php

namespace Pupils\Components\Views\Blog;

use Pupils\Components\Services\Localization\HasLocalization;
use SharedPaws\Models\Blog\BlogPostModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;
use Viewi\Components\DOM\HtmlNode;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\Components\Routing\ClientRoute;

/**
 * /blog/{slug}.
 *
 * Emits `BlogPosting` structured data for the post. Blog posts are usually the pages doing a young
 * site's acquisition work, and article markup is what lets a search engine show a date and a
 * publisher for them, so it belongs to the blog page itself rather than to any one app.
 *
 * IT REFERENCES THE SITE GRAPH BY @id — `{baseUrl}/#organization` and `{baseUrl}/#website` — rather
 * than restating the publisher inline, which is the right shape for a site that emits an
 * `Organization` + `WebSite` graph on every page (Urlicer does it from its header override). A site
 * that does NOT emit that graph leaves two unresolved references: harmless, but pointless. Those
 * sites set `blogJsonLd => false` in the public config and get nothing.
 */
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

    /**
     * The finished JSON-LD document, built once when the post arrives.
     *
     * A property rather than a method called from the template: a value computed per render can be
     * evaluated differently either side of the transpile boundary, and a JSON-LD block that differs
     * between SSR and hydration is a mismatch nobody looks at. Empty renders nothing, which is
     * right while loading and right on a 404.
     */
    public string $articleJson = '';

    /** Absolute site root, for the @id values. */
    public string $siteUrl = '';

    /** Off only when the site says so; absent means on. */
    public bool $jsonLdEnabled = true;

    public function __construct(
        public string $seoName,
        private HttpClient $http,
        private ClientRoute $route,
        ConfigService $configService
    ) {
        $this->siteUrl = $configService->get('baseUrl');
        $this->jsonLdEnabled = $configService->get('blogJsonLd') !== false;
    }

    public function init()
    {
        $this->http->get("/api/blog/{$this->seoName}?next=true")
            ->then(function (array $data) {
                $this->post = $data['post'];
                $this->title = $this->post->MetaTitle ? $this->post->MetaTitle : $this->post->Title;
                $this->nextPost = $data['next'];
                $this->previousPost = $data['previous'];
                if ($this->jsonLdEnabled) {
                    $this->articleJson = $this->buildArticleJson($this->post);
                }
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

    public function buildArticleJson(BlogPostModel $post): string
    {
        $url = $this->siteUrl . '/blog/' . $post->Slug;
        // MetaDescription is written for the SERP and is the better summary where it exists;
        // BodyOverview is the card blurb and the honest fallback. Never the body: a description
        // that is really an article is what gets structured data ignored.
        $description = $post->MetaDescription ? $post->MetaDescription : $post->BodyOverview;

        $article = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            '@id' => $url . '#article',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'headline' => $post->Title,
            'description' => $description,
            'url' => $url,
            'datePublished' => $this->isoTime($post->CreatedOn),
            'dateModified' => $this->isoTime($post->UpdatedOn > 0 ? $post->UpdatedOn : $post->CreatedOn),
            'inLanguage' => 'en',
            'isPartOf' => ['@id' => $this->siteUrl . '/#website'],
            'publisher' => ['@id' => $this->siteUrl . '/#organization'],
            // The publishing Organization, not a person. $Author is a user id and is commonly
            // null, and inventing a byline would be the kind of claim structured data is worst
            // for: machine-readable, and false.
            'author' => ['@id' => $this->siteUrl . '/#organization'],
        ];
        if ($post->Picture !== null && $post->Picture->Path) {
            $article['image'] = $this->absoluteUrl($post->Picture->Path);
        }
        return json_encode($article);  // no JSON_* flags: PHP constants do not exist in the
        // transpiled JS (ReferenceError in the browser); escaped slashes are still valid JSON.
    }

    /**
     * Fluffy timestamps are bigint MICROseconds, not seconds. Composed from two plain gmdate()
     * calls rather than one 'c' or an escaped 'Y-m-d\TH:i:s': only simple format characters are
     * certain to survive the transpile, and this must produce the same string in PHP and in the
     * browser or it is a hydration mismatch.
     */
    public function isoTime(int $microseconds): string
    {
        $seconds = (int) ($microseconds / 1000000);
        return gmdate('Y-m-d', $seconds) . 'T' . gmdate('H:i:s', $seconds) . 'Z';
    }

    public function absoluteUrl(string $path): string
    {
        return strpos($path, 'http') === 0 ? $path : $this->siteUrl . $path;
    }

    public function onBlogSet($response)
    {
        /** open to overrides */
    }
}
