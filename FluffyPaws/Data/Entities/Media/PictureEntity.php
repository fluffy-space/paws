<?php

namespace FluffyPaws\Data\Entities\Media;

use Fluffy\Data\Entities\BaseEntity;

class PictureEntity extends BaseEntity
{
    public string $MimeType;
    public ?string $SeoFilename = null;
    public ?string $AltAttribute = null;
    public ?string $TitleAttribute = null;
    public string $Path;
}
