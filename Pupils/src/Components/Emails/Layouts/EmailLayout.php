<?php

namespace Pupils\Components\Emails\Layouts;

use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;

class EmailLayout extends BaseComponent
{
    public static bool $_noBrowser = true;

    public string $title = 'Email';
    public string $baseUrl = '/';

    public function __construct(ConfigService $configService)
    {
        $this->baseUrl = $configService->get('baseUrl');
    }
}
