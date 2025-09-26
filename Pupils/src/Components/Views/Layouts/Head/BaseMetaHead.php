<?php

namespace Pupils\Components\Views\Layouts\Head;

use Pupils\Components\Services\Layouts\PageMetaService;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;

class BaseMetaHead extends BaseComponent
{
    public string $baseUrl = '/';

    public function __construct(
        public PageMetaService $meta,
        ConfigService $configService
    ) {
        $this->baseUrl = $configService->get('baseUrl');
    }
}
