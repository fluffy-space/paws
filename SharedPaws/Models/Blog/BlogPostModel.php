<?php

namespace SharedPaws\Models\Blog;

use SharedPaws\Models\BaseModel;
use SharedPaws\Models\Media\PictureModel;

class BlogPostModel extends BaseModel
{
    public bool $IncludeInSitemap = true;
    public string $Title = '';
    public string $Slug = '';
    public string $Body = '';
    public string $BodyOverview = '';
    public ?int $Author = null;
    public ?int $PictureId = null;
    public ?PictureModel $Picture = null;
    public bool $Published = false;
    public bool $AllowComments = false;
    public ?string $Tags = null;
    public ?string $MetaKeywords = null;
    public ?string $MetaTitle = null;
    public ?string $MetaDescription  = null;
}
