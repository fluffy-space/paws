<?php

namespace FluffyPaws\Data\Entities\Menu;

use Fluffy\Data\Entities\BaseEntityMap;
use Fluffy\Data\Entities\CommonMap;

class MenuItemEntityMap extends BaseEntityMap
{
    public static string $Table = 'MenuItem';

    public const PROPERTY_Title = 'Title';
    public const PROPERTY_Location = 'Location';
    public const PROPERTY_Order = 'Order';
    public const PROPERTY_Published = 'Published';
    public const CACHE_KEY = "MenuItem-%d";

    public static array $Indexes = [
        'IX_Location' => [
            'Columns' => [self::PROPERTY_Location],
            'Unique' => false
        ]
    ];

    public static function Columns(): array
    {
        return  [
            'Id' => CommonMap::$Id,
            'Title' => CommonMap::$TextCaseInsensitive,
            'Link' => CommonMap::$TextCaseInsensitiveNull,
            'NewTab' => CommonMap::$Boolean,
            self::PROPERTY_Published => CommonMap::$Boolean,
            'LinkClass' => CommonMap::$TextCaseInsensitiveNull,
            'Icon' => CommonMap::$TextCaseInsensitiveNull,
            self::PROPERTY_Order => CommonMap::$Int,
            self::PROPERTY_Location => CommonMap::$Int,
            'Row' => CommonMap::$Int,
            'Column' => CommonMap::$Int,

            'CreatedOn' => CommonMap::$MicroDateTime,
            'CreatedBy' => CommonMap::$VarChar255Null,
            'UpdatedOn' => CommonMap::$MicroDateTime,
            'UpdatedBy' => CommonMap::$VarChar255Null,
        ];
    }
}
