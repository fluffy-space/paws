<?php

namespace FluffyPaws\Data\Repositories;

use FluffyPaws\Data\Entities\Localization\LocaleResourceEntity;
use FluffyPaws\Data\Entities\Localization\LocaleResourceEntityMap;
use DotDi\Attributes\Inject;
use Fluffy\Data\Repositories\BasePostgresqlRepository;

#[Inject(['entityType' => LocaleResourceEntity::class, 'entityMap' => LocaleResourceEntityMap::class])]
class LocaleResourceRepository extends BasePostgresqlRepository
{
}
