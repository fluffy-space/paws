<?php

namespace FluffyPaws\Data\Entities\Content;

use Fluffy\Data\Entities\BaseEntity;
use FluffyPaws\Data\Entities\Media\PictureEntity;

class PageEntity extends BaseEntity
{
    public bool $IncludeInSitemap = true;
    public string $Title;
    public string $Slug;
    public bool $AsHtml = false;
    public string $Body;
    public ?int $PictureId = null;
    public ?PictureEntity $Picture = null;
    public bool $Published = false;
    public ?string $MetaKeywords = null;
    public ?string $MetaTitle = null;
    public ?string $MetaDescription  = null;
}
