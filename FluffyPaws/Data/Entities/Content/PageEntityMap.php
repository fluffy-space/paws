<?php

namespace FluffyPaws\Data\Entities\Content;

use Fluffy\Data\Entities\BaseEntityMap;
use Fluffy\Data\Entities\CommonMap;
use FluffyPaws\Data\Entities\Media\PictureEntity;

class PageEntityMap extends BaseEntityMap
{
    public static string $Table = 'Page';

    public const PROPERTY_Slug = 'Slug';
    public const PROPERTY_Title = 'Title';
    public const PROPERTY_Published = 'Published';
    public const PROPERTY_IncludeInSitemap = 'IncludeInSitemap';

    public static array $Indexes = [
        'UX_Slug' => [
            'Columns' => [self::PROPERTY_Slug],
            'Unique' => true
        ]
    ];

    public static function ForeignKeys(): array
    {
        return [
            'Picture' => [
                'Table' => PictureEntity::class,
                'Columns' => ['PictureId'],
                'References' => ['Id'],
                'OnDelete' => CommonMap::$OnDeleteSetNull
            ]
        ];
    }

    public static function Columns(): array
    {
        return  [
            'Id' => CommonMap::$Id,
            'IncludeInSitemap' => CommonMap::$Boolean,
            self::PROPERTY_Slug => CommonMap::$VarChar400,
            'Title' => CommonMap::$TextCaseInsensitive,
            'AsHtml' => CommonMap::$Boolean,
            'Body' => CommonMap::$TextCaseInsensitive,
            'PictureId' => CommonMap::$BigIntNull,
            'Published' => CommonMap::$Boolean,
            'MetaKeywords' => CommonMap::$VarChar400Null,
            'MetaTitle' => CommonMap::$VarChar400Null,
            'MetaDescription' => CommonMap::$VarChar400Null,

            'CreatedOn' => CommonMap::$MicroDateTime,
            'CreatedBy' => CommonMap::$VarChar255Null,
            'UpdatedOn' => CommonMap::$MicroDateTime,
            'UpdatedBy' => CommonMap::$VarChar255Null,
        ];
    }
}
