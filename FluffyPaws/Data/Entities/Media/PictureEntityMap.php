<?php

namespace FluffyPaws\Data\Entities\Media;

use Fluffy\Data\Entities\BaseEntityMap;
use Fluffy\Data\Entities\CommonMap;

class PictureEntityMap extends BaseEntityMap
{
    public static string $Table = 'Picture';
    public static array $Indexes = [];
    public static function Columns(): array
    {
        return  [
            'Id' => CommonMap::$Id,
            'MimeType' => CommonMap::VarChar(40),
            'SeoFilename' => CommonMap::VarChar(300, true),
            'AltAttribute' => CommonMap::$TextCaseInsensitiveNull,
            'TitleAttribute' => CommonMap::$TextCaseInsensitiveNull,
            'Path' => CommonMap::$TextCaseInsensitive,

            'CreatedOn' => CommonMap::$MicroDateTime,
            'CreatedBy' => CommonMap::$VarChar255Null,
            'UpdatedOn' => CommonMap::$MicroDateTime,
            'UpdatedBy' => CommonMap::$VarChar255Null,
        ];
    }
}
