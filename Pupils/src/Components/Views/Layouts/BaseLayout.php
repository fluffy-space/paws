<?php

namespace Pupils\Components\Views\Layouts;

use Pupils\Components\Services\Layouts\LayoutService;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;

class BaseLayout extends BaseComponent
{
    public string $title = 'Viewi';
    public ?string $description = null;
    public ?string $keywords = null;
    public string $pirschKey = '';

    public function __construct(public LayoutService $layout, ConfigService $configService)
    {
        $this->pirschKey = $configService->get('pirschKey') ?? '';
    }
}
