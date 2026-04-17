<?php

namespace FluffyPaws\Controllers;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Context\DbContext;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Query\Query;
use FluffyPaws\Data\Entities\Content\PageEntity;
use SharedPaws\Models\Content\PageModel;

use function Fluffy\Data\Query\c;
use function Fluffy\Data\Query\from;
use function Fluffy\Data\Query\x;

class ContentController extends BaseController
{
    public function __construct(
        protected IMapper $mapper,
        protected DbContext $db
    ) {}

    public function GetByPath(string $path)
    {
        $path = trim($path, "/");
        /**
         * @var PageEntity|null $entity
         */
        $entity = $this->db->execute(
            from(PageEntity::class)
                ->include('Picture')
                ->where(x(c('Slug'), '=', $path))
                ->firstOrDefault()
        );
        if ($entity === null || !$entity->Published) {
            return $this->NotFound();
        }

        if ($entity === null || !$entity->Published) {
            return $this->NotFound();
        }

        /**
         * @var PageModel $model
         */
        $model = $this->mapper->map(PageModel::class, $entity);
        return $model;
    }
}
