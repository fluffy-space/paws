<?php

namespace FluffyPaws\Data\Entities\Emails;

use Fluffy\Data\Entities\BaseEntityMap;
use Fluffy\Data\Entities\CommonMap;

class EmailLogEntityMap extends BaseEntityMap
{
    public static string $Table = 'EmailLog';

    public const PROPERTY_Recipient = 'Recipient';
    public const PROPERTY_Type = 'Type';
    public const PROPERTY_Subject = 'Subject';
    public const PROPERTY_Status = 'Status';
    public const PROPERTY_CreatedOn = 'CreatedOn';

    public static array $Indexes = [
        'IX_EmailLog_CreatedOn' => [
            'Columns' => [self::PROPERTY_CreatedOn],
            'Unique' => false
        ]
    ];

    public static function Columns(): array
    {
        return [
            'Id' => CommonMap::$Id,
            'Recipient' => CommonMap::$VarChar255,
            'RecipientName' => CommonMap::$VarChar255Null,
            'Type' => CommonMap::$VarChar255,
            'Subject' => CommonMap::$TextCaseInsensitive,
            'Status' => CommonMap::$VarChar255,
            'Error' => CommonMap::$TextNull,
            'Body' => CommonMap::$TextNull,
            'SentOn' => CommonMap::$MicroDateTimeNull,

            'CreatedOn' => CommonMap::$MicroDateTime,
            'CreatedBy' => CommonMap::$VarChar255Null,
            'UpdatedOn' => CommonMap::$MicroDateTime,
            'UpdatedBy' => CommonMap::$VarChar255Null,
        ];
    }
}
