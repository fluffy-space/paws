<?php

namespace FluffyPaws\Controllers;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Context\DbContext;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Query\Query;
use FluffyPaws\Data\Entities\Blog\BlogPostEntity;
use SharedPaws\Models\Blog\BlogPostModel;

use function Fluffy\Data\Query\c;
use function Fluffy\Data\Query\from;
use function Fluffy\Data\Query\x;

class BlogController extends BaseController
{
    public function __construct(
        protected IMapper $mapper,
        protected DbContext $db
    ) {}

    public function GetBySeoName(string $seoName, bool $next = false)
    {
        $entity = $this->db->execute(
            from(BlogPostEntity::class)
                ->include('Picture')
                ->where(x(c('Slug'), '=', $seoName))
                ->firstOrDefault()
        );
        if ($entity === null || !$entity->Published) {
            return $this->NotFound();
        }
        /**
         * @var BlogPostModel $model
         */
        $model = $this->mapper->map(BlogPostModel::class, $entity);
        $result = [
            'post' => $model,
            'next' => null,
            'previous' => null
        ];
        if ($next) {
            $previousPost = $this->db->execute(
                from(BlogPostEntity::class)
                    ->select(['Slug', 'Title'])
                    ->where(x(c('Id'), '>', $model->Id)
                    ->and(c('Published'), '=', true))
                    ->orderBy('Id')
                    ->firstOrDefault()
            );
            $nextPost = $this->db->execute(
                from(BlogPostEntity::class)
                    ->select(['Slug', 'Title'])
                    ->where(x(c('Id'), '<', $model->Id)
                    ->and(c('Published'), '=', true))
                    ->orderByDescending('Id')
                    ->firstOrDefault()
            );
            $result['next'] = $nextPost ? ['Slug' => $nextPost->Slug, 'Title' => $nextPost->Title] : null;
            $result['previous'] = $previousPost ? ['Slug' => $previousPost->Slug, 'Title' => $previousPost->Title] : null;
        }
        return $result;
    }

    public function GetList(int $page = 1, int $size = 10)
    {
        $entities = $this->db->execute(
            from(BlogPostEntity::class)
                ->include('Picture')
                ->where(x(c('Published'), '=', true))
                ->orderByDescending('Id')
                ->page($page)
                ->take($size)
        );
        $models = $models = array_map(fn($entity) => $this->mapper->map(BlogPostModel::class, $entity), $entities['list']);
        return ['list' => $models, 'total' => $entities['total']];
    }
}
