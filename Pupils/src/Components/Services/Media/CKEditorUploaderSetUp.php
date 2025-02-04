<?php

namespace Pupils\Components\Services\Media;

use Viewi\Bridge\IViewiBridge;
use Viewi\Components\Environment\Platform;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\IStartUp\IStartUp;
use Viewi\DI\Singleton;
use Viewi\UI\Components\CKEditor\CKEditor;

#[Singleton]
class CKEditorUploaderSetUp implements IStartUp
{
    public function __construct(private Platform $platform, private HttpClient $http) {}

    public function getAdapter($loader): CKEditorFileUploader
    {
        return new CKEditorFileUploader($loader, $this->http);
    }

    public function setUp()
    {
        CKEditor::$fileAdapter = function ($loader) {
            return $this->getAdapter($loader);
        };
    }
}
