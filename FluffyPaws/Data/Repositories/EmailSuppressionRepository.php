<?php

namespace FluffyPaws\Data\Repositories;

use DotDi\Attributes\Inject;
use Fluffy\Data\Repositories\BasePostgresqlRepository;
use FluffyPaws\Data\Entities\Emails\EmailSuppressionEntity;
use FluffyPaws\Data\Entities\Emails\EmailSuppressionEntityMap;

#[Inject(['entityType' => EmailSuppressionEntity::class, 'entityMap' => EmailSuppressionEntityMap::class])]
class EmailSuppressionRepository extends BasePostgresqlRepository
{
    /**
     * Look an address up. Expects an already-normalised (lower-cased) address —
     * EmailSuppressionService::normalize() is the only intended caller path.
     */
    public function findByEmail(string $normalizedEmail): ?EmailSuppressionEntity
    {
        /** @var ?EmailSuppressionEntity */
        return $this->find(EmailSuppressionEntityMap::PROPERTY_Email, $normalizedEmail);
    }
}
