<?php

namespace FluffyPaws\Data\Entities\Blog;

use Fluffy\Data\Entities\BaseEntity;
use FluffyPaws\Data\Entities\Media\PictureEntity;

class BlogPostEntity extends BaseEntity
{
    public bool $IncludeInSitemap = true;
    public string $Title;
    public string $Slug;
    public string $Body;
    public string $BodyOverview;
    public ?int $Author = null;
    public ?int $PictureId = null;    
    public ?PictureEntity $Picture = null;
    public bool $Published = false;
    public bool $AllowComments = false;
    public ?string $Tags = null;
    public ?string $MetaKeywords = null;
    public ?string $MetaTitle = null;
    public ?string $MetaDescription  = null;
}
