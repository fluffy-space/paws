<?php

namespace SharedPaws\Models\Content;

use SharedPaws\Models\BaseModel;
use SharedPaws\Models\Media\PictureModel;

class PageModel extends BaseModel
{
    public bool $IncludeInSitemap = true;
    public ?string $Title = '';
    public string $Slug = '';
    public bool $HomePage = false;
    public bool $AsHtml = false;
    public ?string $Body = '';
    public ?string $MetaKeywords = null;
    public ?string $MetaTitle = null;
    public ?string $MetaDescription  = null;
    public ?int $PictureId = null;
    public ?PictureModel $Picture = null;
    public bool $Published = false;
}
