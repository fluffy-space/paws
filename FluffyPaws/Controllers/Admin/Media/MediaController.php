<?php

namespace FluffyPaws\Controllers\Admin\Media;

use FluffyPaws\Data\Entities\Media\PictureEntity;
use FluffyPaws\Data\Repositories\PictureRepository;
use Fluffy\Controllers\BaseController;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Domain\Configuration\Config;
use Fluffy\Services\Auth\AuthorizationService;
use SharedPaws\Models\Media\PictureModel;
use Swoole\Coroutine\System;

class MediaController extends BaseController
{
    function __construct(
        protected IMapper $mapper,
        protected PictureRepository $pictures,
        protected AuthorizationService $auth,
        protected Config $config
    ) {
    }

    public function Upload(string $fileName, string $type, string $data)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }
        $targetDirectory = $this->config->values['publicDir'] . '/images';
        $picture = new PictureEntity();
        $picture->MimeType = $type;
        $picture->SeoFilename = $fileName;
        $picture->Path = '/images/' . $fileName;

        if (!file_exists($targetDirectory)) {
            mkdir($targetDirectory, 0777, true);
        }
        // save file
        $destination = $this->config->values['publicDir'] . $picture->Path;
        $ok = System::writeFile($destination, $data);
        if (!$ok) {
            return $this->BadRequest(['File write has failed.']);
        }
        $this->pictures->create($picture);
        return $this->mapper->map(PictureModel::class, $picture);
    }
}
