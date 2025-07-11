<?php

namespace Pupils\Components\Views\Auth;

use Viewi\Components\BaseComponent;

class EmailVerificationPage extends BaseComponent
{
    public bool $hasFailed = false;

    public function __construct(?string $failed)
    {
        $this->hasFailed = $failed === 'failed';
    }
}
