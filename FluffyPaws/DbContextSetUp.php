<?php

namespace FluffyPaws;

use Fluffy\Data\Context\DbContext;
use FluffyPaws\Data\Entities\Blog\BlogPostEntity;
use FluffyPaws\Data\Entities\Blog\BlogPostEntityMap;

/** @namespaces **/
// !Do not delete the line above!

class DbContextSetUp
{
    public static function configure()
    {
        DbContext::registerEntity(BlogPostEntity::class, BlogPostEntityMap::class);
        /** @insert **/
        // !Do not delete the line above!
    }
}
