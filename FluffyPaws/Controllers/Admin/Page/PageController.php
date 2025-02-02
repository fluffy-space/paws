<?php

namespace FluffyPaws\Controllers\Admin\Page;


use Fluffy\Controllers\BaseController;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Services\Auth\AuthorizationService;
use FluffyPaws\Data\Entities\Content\PageEntity;
use FluffyPaws\Data\Entities\Content\PageEntityMap;
use FluffyPaws\Data\Repositories\PageRepository;
use FluffyPaws\Data\Repositories\PictureRepository;
use FluffyPaws\Services\Sitemap\SitemapService;
use FluffyPaws\Services\Utils\SlugService;
use SharedPaws\Models\Content\PageModel;
use SharedPaws\Models\Content\PageValidation;
use SharedPaws\Models\Media\PictureModel;

class PageController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected PageRepository $pages,
        protected PictureRepository $pictures,
        protected AuthorizationService $auth,
        protected SitemapService $sitemapService
    ) {
    }

    public function List(int $page = 1, int $size = 10, ?string $search = null)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $search = trim($search ?? '');
        $where = [];
        if ($search) {
            $where = [[PageEntityMap::PROPERTY_Title, 'like', "%$search%"]];
        }
        $entities = $this->pages->search($where, [PageEntityMap::PROPERTY_CreatedOn => 1], $page, $size);
        $models = array_map(fn ($entity) => $this->mapper->map(PageModel::class, $entity), $entities['list']);
        return ['list' => $models, 'total' => $entities['total']];
    }

    public function Get(int $id)
    {
        $entity = $this->pages->getById($id);
        if (!$entity) {
            return $this->NotFound();
        }
        /**
         * @var PageModel $model
         */
        $model = $this->mapper->map(PageModel::class, $entity);
        $model->HomePage = $model->Slug === '';
        if ($model->PictureId !== null) {
            /**
             * @var ?PictureEntity $picture
             */
            $picture = $this->pictures->getById($model->PictureId);
            $model->Picture = $this->mapper->map(PictureModel::class, $picture);
        }
        return $model;
    }

    public function Update(int $id, PageModel $page)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new PageValidation($page))->getValidationRules();
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
        $pageEntity = $this->pages->getById($id);
        if (!$pageEntity) {
            return $this->NotFound();
        }
        $fixSlug = false;
        if ($page->HomePage) {
            $page->Slug = '';
        }
        if (!$page->HomePage && ($page->Slug === null || $page->Slug === '')) {
            $page->Slug = SlugService::slugify($page->Title);
            $fixSlug = true;
        }
        $baseSlug = $page->Slug;
        $slugIndex = 0;
        $slugPost = $this->pages->getBySlug($page->Slug);
        while ($slugPost !== null && $slugPost->Id !== $id) {
            if (!$fixSlug) {
                return $this->BadRequest(["Page with Slug '{$page->Slug}' already exists. Please choose another one."]);
            }
            $page->Slug = $baseSlug . '-' . (++$slugIndex);
            $slugPost = $this->pages->getBySlug($page->Slug);
        }
        $pageEntity = $this->mapper->map(PageEntity::class, $page, $pageEntity);
        $success = $this->pages->update($pageEntity);
        $model = null;
        if ($success) {
            /**
             * @var PageModel $model
             */
            $model = $this->mapper->map(PageModel::class, $pageEntity);
            $model->HomePage = $model->Slug === '';
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
        $pageEntity = $this->pages->getById($id);
        if (!$pageEntity) {
            return $this->NotFound();
        }
        $success = $this->pages->delete($pageEntity);
        $this->sitemapService->resetCache();
        return $success;
    }

    public function Create(PageModel $page)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $validationMessages = [];
        $validationRules = (new PageValidation($page))->getValidationRules();
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
        if ($page->HomePage) {
            $page->Slug = '';
        }
        if (!$page->HomePage && ($page->Slug === null || $page->Slug === '')) {
            $page->Slug = SlugService::slugify($page->Title);
            $fixSlug = true;
        }
        $baseSlug = $page->Slug;
        $slugIndex = 0;
        while ($this->pages->getBySlug($page->Slug) !== null) {
            if (!$fixSlug) {
                return $this->BadRequest(["Page with Slug '{$page->Slug}' already exists. Please choose another one."]);
            }
            $page->Slug = $baseSlug . '-' . (++$slugIndex);
        }

        $pageEntity = $this->mapper->map(PageEntity::class, $page);
        $success = $this->pages->create($pageEntity);
        $this->sitemapService->resetCache();
        return $success ? $this->mapper->map(PageModel::class, $pageEntity) : null;
    }
}
