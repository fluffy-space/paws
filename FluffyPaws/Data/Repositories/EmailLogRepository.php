<?php

namespace FluffyPaws\Data\Repositories;

use FluffyPaws\Data\Entities\Emails\EmailLogEntity;
use FluffyPaws\Data\Entities\Emails\EmailLogEntityMap;
use DotDi\Attributes\Inject;
use Fluffy\Data\Repositories\BasePostgresqlRepository;

#[Inject(['entityType' => EmailLogEntity::class, 'entityMap' => EmailLogEntityMap::class])]
class EmailLogRepository extends BasePostgresqlRepository
{
    /**
     * Hard-delete every log row created before $microTimestamp (bigint microseconds).
     * Single statement — used by EmailLogCleanupTask for retention pruning.
     *
     * @return int number of rows removed
     */
    public function deleteOlderThan(int $microTimestamp): int
    {
        $sql = "DELETE FROM {$this->entityMap::$Schema}.\"{$this->entityMap::$Table}\""
            . " WHERE \"" . EmailLogEntityMap::PROPERTY_CreatedOn . "\" < {$microTimestamp};";
        $this->connector->query($sql);
        return $this->connector->affectedRows();
    }
}
