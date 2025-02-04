<?php

namespace Pupils\Components\Services\Media;

use SharedPaws\Models\Media\PictureModel;
use Viewi\Components\Attributes\IncludeAlways;
use Viewi\Components\DOM\DomFile;
use Viewi\Components\Http\HttpClient;
use Viewi\UI\Components\CKEditor\CKEditorUploader;
use Viewi\UI\Components\CKEditor\CKEUploadRequest;

class CKEditorFileUploader extends CKEditorUploader
{
    public function __construct(public $loader, private HttpClient $http) {}

    function uploadFile(DomFile $file, CKEUploadRequest $request)
    {
        $this->http->post('/api/admin/picture/upload?fileName=' . $file->name . "&type=" . $file->type, $file)
            ->then(function (PictureModel $picture) use ($request) {
                $request->success($picture->Path);
            }, function ($err) use ($request) {
                $request->error($err);
            });
    }

    // Aborts the upload process.
    public function abort() {}
}
