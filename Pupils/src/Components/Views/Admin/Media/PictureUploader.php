<?php

namespace Pupils\Components\Views\Admin\Media;

use SharedPaws\Models\Media\PictureModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;
use Viewi\UI\Components\Alerts\AlertService;

class PictureUploader extends BaseComponent
{
    public ?string $id = null;
    public ?string $label = null;
    public bool $showMessages = true;
    public ?PictureModel $picture = null;

    public function __construct(
        private HttpClient $http,
        private AlertService $messages,
    ) {}

    public function removePicture(DomEvent $event)
    {
        $event->preventDefault();
        $this->picture = null;
        $this->emitEvent('removed', null);
    }

    // file upload
    public function fileChanged(DomEvent $event)
    {
        $files = $event->target->files;
        // TODO: make utils
        <<<'javascript'
            files = Array.prototype.slice.call(files);
            javascript;
        if (count($files) > 0) {
            $file = $files[0];
            $this->http->post(
                "/api/admin/picture/upload?fileName="
                    . urlencode($file->name)
                    . "&type="
                    . urlencode($file->type),
                $file
            )->then(
                function (PictureModel $picture) {
                    $this->picture = $picture;
                    $this->emitEvent('uploaded', $picture);
                    if ($this->showMessages) {
                        $this->messages->success('File has been successfully uploaded', 5000);
                    }
                },
                function ($error) {
                    echo $error;
                    if ($this->showMessages) {
                        $this->messages->error('File upload has failed', 5000);
                    }
                }
            );
        }
    }
}
