<?php

namespace FluffyPaws\Controllers;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Context\DbContext;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Query\Query;
use FluffyPaws\Data\Entities\Blog\BlogPostEntity;
use SharedPaws\Models\Blog\BlogPostModel;

class BlogController extends BaseController
{
    public function __construct(
        protected IMapper $mapper,
        protected DbContext $db
    ) {}

    public function GetBySeoName(string $seoName)
    {
        $entity = $this->db->execute(
            Query::from(BlogPostEntity::class)
                ->include('Picture')
                ->where(['Slug', '=', $seoName])
                ->firstOrDefault()
        );
        if ($entity === null || !$entity->Published) {
            return $this->NotFound();
        }
        /**
         * @var BlogPostModel $model
         */
        $model = $this->mapper->map(BlogPostModel::class, $entity);
        return $model;
    }

    public function GetList(int $page = 1, int $size = 10)
    {
        $entities = $this->db->execute(
            Query::from(BlogPostEntity::class)
                ->include('Picture')
                ->where(['Published', '=', true])
                ->orderByDescending('CreatedOn')
                ->withCount(false)
                ->page($page)
                ->take($size)
        );
        $models = $models = array_map(fn($entity) => $this->mapper->map(BlogPostModel::class, $entity), $entities['list']);
        return $models;
    }
}
