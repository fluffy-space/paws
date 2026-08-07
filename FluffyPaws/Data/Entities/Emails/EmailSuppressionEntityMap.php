<?php

namespace FluffyPaws\Data\Entities\Emails;

use Fluffy\Data\Entities\BaseEntityMap;
use Fluffy\Data\Entities\CommonMap;

class EmailSuppressionEntityMap extends BaseEntityMap
{
    public static string $Table = 'EmailSuppression';

    public const PROPERTY_Email = 'Email';
    public const PROPERTY_Reason = 'Reason';
    public const PROPERTY_Source = 'Source';
    public const PROPERTY_CreatedOn = 'CreatedOn';

    public static array $Indexes = [
        'UX_EmailSuppression_Email' => [
            'Columns' => [self::PROPERTY_Email],
            'Unique' => true
        ]
    ];

    public static function Columns(): array
    {
        return [
            'Id' => CommonMap::$Id,
            'Email' => CommonMap::$VarChar255,
            'Reason' => CommonMap::$VarChar255,
            'Source' => CommonMap::$VarChar255,
            'Detail' => CommonMap::$TextNull,
            'LastEventOn' => CommonMap::$MicroDateTimeNull,

            'CreatedOn' => CommonMap::$MicroDateTime,
            'CreatedBy' => CommonMap::$VarChar255Null,
            'UpdatedOn' => CommonMap::$MicroDateTime,
            'UpdatedBy' => CommonMap::$VarChar255Null,
        ];
    }
}
