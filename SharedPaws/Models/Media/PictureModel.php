<?php

namespace SharedPaws\Models\Media;

use SharedPaws\Models\BaseModel;

class PictureModel extends BaseModel
{
    public string $MimeType;
    public ?string $SeoFilename = null;
    public ?string $AltAttribute = null;
    public ?string $TitleAttribute = null;
    public string $Path;
}
