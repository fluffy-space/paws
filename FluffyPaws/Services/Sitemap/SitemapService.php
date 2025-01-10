<?php

namespace FluffyPaws\Services\Sitemap;

use FluffyPaws\Data\Entities\Blog\BlogPostEntity;
use FluffyPaws\Data\Entities\Blog\BlogPostEntityMap;
use FluffyPaws\Data\Entities\Content\PageEntity;
use FluffyPaws\Data\Entities\Content\PageEntityMap;
use FluffyPaws\Data\Repositories\BlogPostRepository;
use FluffyPaws\Data\Repositories\PageRepository;
use DotDi\DependencyInjection\Container;
use Fluffy\Domain\Configuration\Config;
use Fluffy\Swoole\Cache\CacheManager;

class SitemapService
{
    const CACHE_SITEMAP_KEY = 'Sitemap';

    public function __construct(
        protected CacheManager $cache,
        protected Container $container
    ) {
    }

    public function resetCache()
    {
        $this->cache->delete(self::CACHE_SITEMAP_KEY);
    }

    public function getRobotsTxt()
    {
        /**
         * @var Config $config
         */
        $config = $this->container->serviceProvider->get(Config::class);
        $baseUrl = $config->values['baseUrl'];
        return "User-agent: *
Sitemap: $baseUrl/sitemap.xml
Host: $baseUrl/
Disallow: /admin
Disallow: /login
Disallow: /register
Disallow: /reset-password
Disallow: /account
Disallow: /password/reset
Disallow: /order";
    }

    public function getSitemap()
    {
        $cacheKey = self::CACHE_SITEMAP_KEY;
        $models = $this->cache->get($cacheKey);
        if ($models === null) {
            $models = $this->cache->set($cacheKey, function () {
                return $this->generateSitemap();
            });
        }
        return $models;
    }

    public function generateSitemap()
    {
        $sitemap = '<?xml version="1.0" encoding="utf-8"?>';
        $sitemap .= '<urlset xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 ';
        $sitemap .= 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd http://www.w3.org/1999/xhtml ';
        $sitemap .= 'http://www.w3.org/2002/08/xhtml/xhtml1-strict.xsd" xmlns:xhtml="http://www.w3.org/1999/xhtml" ';
        $sitemap .= 'xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        /**
         * @var Config $config
         */
        $config = $this->container->serviceProvider->get(Config::class);
        $baseUrl = $config->values['baseUrl'];
        $sitemap .= $this->getPagesSitemap($baseUrl);
        $sitemap .= $this->getStaticSitemap($baseUrl);
        $sitemap .= $this->getBlogSitemap($baseUrl);

        $sitemap .= '</urlset>';
        return $sitemap;
    }

    protected function getPagesSitemap(string $baseUrl)
    {
        $sitemap = '';
        /**
         * @var PageRepository $pages
         */
        $pages = $this->container->serviceProvider->get(PageRepository::class);
        $where = [
            [PageEntityMap::PROPERTY_Published, true],            
            [PageEntityMap::PROPERTY_IncludeInSitemap, true]
        ];
        /**
         * @var PageEntity[] $contentPages
         */
        $contentPages = $pages->search($where, [PageEntityMap::PROPERTY_CreatedOn => -1], 1, null, false)['list'];
        foreach ($contentPages as $page) {
            $seconds = $page->UpdatedOn / 1000000;
            $lastMod = gmdate('Y-m-d', (int)$seconds);
            $url = $baseUrl . '/' . $page->Slug;
            $sitemap .= "<url><loc>$url</loc><changefreq>weekly</changefreq><lastmod>$lastMod</lastmod></url>";
        }
        return $sitemap;
    }

    protected function getBlogSitemap(string $baseUrl)
    {
        $sitemap = '';
        /**
         * @var BlogPostRepository $pages
         */
        $pages = $this->container->serviceProvider->get(BlogPostRepository::class);
        $where = [
            [BlogPostEntityMap::PROPERTY_Published, true],            
            [BlogPostEntityMap::PROPERTY_IncludeInSitemap, true]
        ];
        /**
         * @var BlogPostEntity[] $contentPages
         */
        $contentPages = $pages->search($where, [BlogPostEntityMap::PROPERTY_CreatedOn => -1], 1, null, false)['list'];
        foreach ($contentPages as $page) {
            $seconds = $page->UpdatedOn / 1000000;
            $lastMod = gmdate('Y-m-d', (int)$seconds);
            $url = $baseUrl . '/blog/' . $page->Slug;
            $sitemap .= "<url><loc>$url</loc><changefreq>weekly</changefreq><lastmod>$lastMod</lastmod></url>";
        }
        return $sitemap;
    }

    protected function getStaticSitemap(string $baseUrl)
    {
        $sitemap = '';
        $pages = [
            '/blog',
            '/catalog'
        ];
        foreach ($pages as $page) {
            $lastMod = gmdate('Y-m-d');
            $url = $baseUrl . $page;
            $sitemap .= "<url><loc>$url</loc><changefreq>weekly</changefreq><lastmod>$lastMod</lastmod></url>";
        }
        return $sitemap;
    }
}
