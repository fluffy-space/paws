<?php

namespace FluffyPaws\Data\Repositories;

use FluffyPaws\Data\Entities\Media\PictureEntity;
use FluffyPaws\Data\Entities\Media\PictureEntityMap;
use DotDi\Attributes\Inject;
use Fluffy\Data\Repositories\BasePostgresqlRepository;

#[Inject(['entityType' => PictureEntity::class, 'entityMap' => PictureEntityMap::class])]
class PictureRepository extends BasePostgresqlRepository
{
}
