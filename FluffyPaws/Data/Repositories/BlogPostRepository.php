<?php

namespace FluffyPaws\Data\Repositories;

use FluffyPaws\Data\Entities\Blog\BlogPostEntity;
use FluffyPaws\Data\Entities\Blog\BlogPostEntityMap;
use DotDi\Attributes\Inject;
use Fluffy\Data\Repositories\BasePostgresqlRepository;

#[Inject(['entityType' => BlogPostEntity::class, 'entityMap' => BlogPostEntityMap::class])]
class BlogPostRepository extends BasePostgresqlRepository
{
    public function getBySlug(string $slug): ?BlogPostEntity
    {
        return $this->find(BlogPostEntityMap::PROPERTY_Slug, $slug);
    }
}
