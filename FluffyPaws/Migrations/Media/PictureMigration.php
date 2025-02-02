<?php

namespace FluffyPaws\Migrations\Media;

use Fluffy\Data\Entities\CommonMap;
use Fluffy\Data\Repositories\MigrationRepository;
use Fluffy\Migrations\BaseMigration;
use FluffyPaws\Data\Repositories\PictureRepository;

class PictureMigration extends BaseMigration
{
    function __construct(MigrationRepository $MigrationHistoryRepository, private PictureRepository $pictureRepository)
    {
        parent::__construct($MigrationHistoryRepository);
    }

    public function up()
    {
        $this->pictureRepository->createTable(
            [
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
            ],
            ['Id'],
            [],
            []
        );
    }

    public function down()
    {
        $this->pictureRepository->dropTable(true, true);
    }
}
