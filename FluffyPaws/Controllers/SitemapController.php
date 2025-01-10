<?php

namespace FluffyPaws\Controllers;

use FluffyPaws\Services\Sitemap\SitemapService;
use Fluffy\Controllers\BaseController;

class SitemapController extends BaseController
{
    public function __construct(
        protected SitemapService $sitemap
    ) {}

    public function Sitemap()
    {
        return $this->Xml($this->sitemap->getSitemap());
    }

    public function Robots()
    {
        return $this->Text($this->sitemap->getRobotsTxt());
    }
}
