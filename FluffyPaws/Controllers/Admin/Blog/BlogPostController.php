<?php

namespace FluffyPaws\Controllers\Admin\Blog;

use Fluffy\Controllers\BaseController;
use Fluffy\Data\Context\DbContext;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Query\Query;
use Fluffy\Services\Auth\AuthorizationService;
use FluffyPaws\Data\Entities\Blog\BlogPostEntity;
use FluffyPaws\Data\Repositories\BlogPostRepository;
use FluffyPaws\Data\Repositories\PictureRepository;
use FluffyPaws\Services\Sitemap\SitemapService;
use FluffyPaws\Services\Utils\SlugService;
use SharedPaws\Models\Blog\BlogPostModel;
use SharedPaws\Models\Blog\BlogValidation;
use SharedPaws\Models\Media\PictureModel;

class BlogPostController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected DbContext $db,
        protected BlogPostRepository $blogs,
        protected PictureRepository $pictures,
        protected AuthorizationService $auth,
        protected SitemapService $sitemapService
    ) {}

    public function List(int $page = 1, int $size = 10, ?string $search = null)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }

        $query = Query::from(BlogPostEntity::class);

        $search = trim($search ?? '');

        if ($search) {
            $search = strtolower($search);
            $parts = explode(' ', $search);
            foreach ($parts as $part) {
                if (trim($part)) {
                    $query->where(Query::or([
                        ['Title', 'like', "%$part%"]
                    ]));
                }
            }
        }

        $query->orderBy('CreatedOn')
            ->include('Picture')
            ->page($page)
            ->take($size);

        $entities = $this->db->execute($query);

        $models = array_map(fn($entity) => $this->mapper->map(BlogPostModel::class, $entity), $entities['list']);
        return ['list' => $models, 'total' => $entities['total']];
    }

    public function Get(int $id)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $entity = $this->db->execute(
            Query::from(BlogPostEntity::class)
                ->include('Picture')
                ->where(['Id', '=', $id])
                ->firstOrDefault()
        );
        if (!$entity) {
            return $this->NotFound();
        }
        /**
         * @var BlogPostModel $model
         */
        $model = $this->mapper->map(BlogPostModel::class, $entity);
        return $model;
    }

    public function Update(int $id, BlogPostModel $blogPost)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new BlogValidation($blogPost))->getValidationRules();
        foreach ($validationRules as $property => $rules) {
            foreach ($rules as $validationRule) {
                $validationResult = $validationRule();
                if ($validationResult !== true) {
                    $validationMessages[] = $validationResult === false ? "Validation has failed for $property." : $validationResult;
                }
            }
        }
        if (count($validationMessages) > 0) {
            return $this->BadRequest($validationMessages);
        }
        // DB validations        
        $blogPostEntity = $this->blogs->getById($id);
        if (!$blogPostEntity) {
            return $this->NotFound();
        }
        $fixSlug = false;
        if ($blogPost->Slug === null || $blogPost->Slug === '') {
            $blogPost->Slug = SlugService::slugify($blogPost->Title);
            $fixSlug = true;
        }
        $baseSlug = $blogPost->Slug;
        $slugIndex = 0;
        $slugPost = $this->blogs->getBySlug($blogPost->Slug);
        while ($slugPost !== null && $slugPost->Id !== $id) {
            if (!$fixSlug) {
                return $this->BadRequest(["Blog post with Slug '{$blogPost->Slug}' already exists. Please choose another one."]);
            }
            $blogPost->Slug = $baseSlug . '-' . (++$slugIndex);
            $slugPost = $this->blogs->getBySlug($blogPost->Slug);
        }
        $blogPostEntity = $this->mapper->map(BlogPostEntity::class, $blogPost, $blogPostEntity);
        $success = $this->blogs->update($blogPostEntity);
        $model = null;
        if ($success) {
            /**
             * @var BlogPostModel $model
             */
            $model = $this->mapper->map(BlogPostModel::class, $blogPostEntity);
            if ($model->PictureId !== null) {
                /**
                 * @var ?PictureEntity $picture
                 */
                $picture = $this->pictures->getById($model->PictureId);
                $model->Picture = $this->mapper->map(PictureModel::class, $picture);;
            }
            $this->sitemapService->resetCache();
        }
        return $model;
    }

    public function Delete(int $id)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $entity = $this->blogs->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        $success = $this->blogs->delete($entity);
        $this->sitemapService->resetCache();
        return $success;
    }

    public function Create(BlogPostModel $blogPost)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new BlogValidation($blogPost))->getValidationRules();
        foreach ($validationRules as $property => $rules) {
            foreach ($rules as $validationRule) {
                $validationResult = $validationRule();
                if ($validationResult !== true) {
                    $validationMessages[] = $validationResult === false ? "Validation has failed for $property." : $validationResult;
                }
            }
        }
        if (count($validationMessages) > 0) {
            return $this->BadRequest($validationMessages);
        }
        // DB validations
        $fixSlug = false;
        if ($blogPost->Slug === null || $blogPost->Slug === '') {
            $blogPost->Slug = SlugService::slugify($blogPost->Title);
            $fixSlug = true;
        }
        $baseSlug = $blogPost->Slug;
        $slugIndex = 0;
        while ($this->blogs->getBySlug($blogPost->Slug) !== null) {
            if (!$fixSlug) {
                return $this->BadRequest(["Blog post with Slug '{$blogPost->Slug}' already exists. Please choose another one."]);
            }
            $blogPost->Slug = $baseSlug . '-' . (++$slugIndex);
        }

        $blogPostEntity = $this->mapper->map(BlogPostEntity::class, $blogPost);
        $success = $this->blogs->create($blogPostEntity);
        $this->sitemapService->resetCache();
        return $success ? $this->mapper->map(BlogPostModel::class, $blogPostEntity) : null;
    }
}
