<?php

namespace FluffyPaws\Data\Entities\Localization;

use Fluffy\Data\Entities\BaseEntityMap;
use Fluffy\Data\Entities\CommonMap;

class LanguageEntityMap extends BaseEntityMap
{
    public static string $Table = 'Language';
    public const PROPERTY_Name = 'Name';
    public const PROPERTY_SeoCode = 'SeoCode';
    public const PROPERTY_Published = 'Published';

    public static array $Indexes = [
        'UX_SeoCode' => [
            'Columns' => [self::PROPERTY_SeoCode],
            'Unique' => true
        ]
    ];

    public static function Columns(): array
    {
        return  [
            'Id' => CommonMap::$Id,
            'Name' => CommonMap::$TextCaseInsensitive,
            'LanguageCulture' => CommonMap::$VarChar255,
            'SeoCode' => CommonMap::$VarChar255,
            'Rtl' => CommonMap::$Boolean,
            'Published' => CommonMap::$Boolean,
            'DisplayOrder' => CommonMap::$Int,

            'CreatedOn' => CommonMap::$MicroDateTime,
            'CreatedBy' => CommonMap::$VarChar255Null,
            'UpdatedOn' => CommonMap::$MicroDateTime,
            'UpdatedBy' => CommonMap::$VarChar255Null,
        ];
    }
}
