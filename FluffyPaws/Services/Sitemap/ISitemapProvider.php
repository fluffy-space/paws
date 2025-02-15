<?php

namespace FluffyPaws\Services\Sitemap;

interface ISitemapProvider
{
    /**
     * 
     * @return array{url: string, lastmod: string}[]
     */
    public function getUrls(): array;
}
