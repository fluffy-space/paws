<?php

namespace FluffyPaws\Data\Entities\Localization;

use Fluffy\Data\Entities\BaseEntityMap;
use Fluffy\Data\Entities\CommonMap;

class LocaleResourceEntityMap extends BaseEntityMap
{    
    public const PROPERTY_Name = 'Name';
    public const PROPERTY_Value = 'Value';
    public const PROPERTY_LanguageId = 'LanguageId';
    public const CACHE_KEY = "LocaleResource-%d";

    public static string $Table = 'LocaleResource';
    public static array $Indexes = [];

    public static function Columns(): array
    {
        return  [
            'Id' => CommonMap::$Id,
            'LanguageId' => CommonMap::$BigInt,
            'Name' => CommonMap::$TextCaseInsensitive,
            'Value' => CommonMap::$TextCaseInsensitive,

            'CreatedOn' => CommonMap::$MicroDateTime,
            'CreatedBy' => CommonMap::$VarChar255Null,
            'UpdatedOn' => CommonMap::$MicroDateTime,
            'UpdatedBy' => CommonMap::$VarChar255Null,
        ];
    }
}
