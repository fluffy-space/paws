<?php

namespace FluffyPaws\Data\Repositories;

use FluffyPaws\Data\Entities\Content\PageEntity;
use FluffyPaws\Data\Entities\Content\PageEntityMap;
use DotDi\Attributes\Inject;
use Fluffy\Data\Repositories\BasePostgresqlRepository;

#[Inject(['entityType' => PageEntity::class, 'entityMap' => PageEntityMap::class])]
class PageRepository extends BasePostgresqlRepository
{
    public function getBySlug(string $slug): ?PageEntity
    {
        return $this->find(PageEntityMap::PROPERTY_Slug, $slug);
    }
}
