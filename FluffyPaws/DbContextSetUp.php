<?php

namespace FluffyPaws;

use Fluffy\Data\Context\DbContext;
use FluffyPaws\Data\Entities\Blog\BlogPostEntity;
use FluffyPaws\Data\Entities\Blog\BlogPostEntityMap;
use FluffyPaws\Data\Entities\Content\PageEntity;
use FluffyPaws\Data\Entities\Content\PageEntityMap;
use FluffyPaws\Data\Entities\Media\PictureEntity;
use FluffyPaws\Data\Entities\Media\PictureEntityMap;

/** @namespaces **/
// !Do not delete the line above!

class DbContextSetUp
{
    public static function configure()
    {
        DbContext::registerEntity(BlogPostEntity::class, BlogPostEntityMap::class);
        DbContext::registerEntity(PageEntity::class, PageEntityMap::class);
        DbContext::registerEntity(PictureEntity::class, PictureEntityMap::class);
        /** @insert **/
        // !Do not delete the line above!
    }
}
