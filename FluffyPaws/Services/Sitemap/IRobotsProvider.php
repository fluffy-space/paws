<?php

namespace FluffyPaws\Services\Sitemap;

/**
 * Lets an app contribute its own Disallow rules to robots.txt, the same way ISitemapProvider
 * lets it contribute URLs to sitemap.xml. SitemapService resolves every registered
 * implementation with getAll() and merges the results into the framework defaults.
 *
 * Needed because the default list can only cover routes the framework itself owns. Every app
 * has private areas the framework has never heard of, and the alternative — each app editing
 * the core default — makes the core list wrong for everyone else.
 */
interface IRobotsProvider
{
    /**
     * Path prefixes to disallow, each starting with '/'. Duplicates of the framework
     * defaults are dropped, so returning one is harmless.
     *
     * @return string[]
     */
    public function getDisallow(): array;
}
