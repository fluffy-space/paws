<?php

namespace FluffyPaws\Data\Repositories;

use FluffyPaws\Data\Entities\Localization\LanguageEntity;
use FluffyPaws\Data\Entities\Localization\LanguageEntityMap;
use DotDi\Attributes\Inject;
use Fluffy\Data\Repositories\BasePostgresqlRepository;

#[Inject(['entityType' => LanguageEntity::class, 'entityMap' => LanguageEntityMap::class])]
class LanguageRepository extends BasePostgresqlRepository
{
}
