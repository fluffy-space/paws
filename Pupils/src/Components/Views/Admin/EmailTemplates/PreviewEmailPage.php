<?php

namespace Pupils\Components\Views\Admin\EmailTemplates;

use Pupils\Components\Guards\HasCapability;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\BaseComponent;

#[Middleware([[HasCapability::class, 'ManageEmailTemplates']])]
class PreviewEmailPage extends BaseComponent
{
    public array $templates = [
        'reset-password' => 'Reset password',
        'confirm-email' => 'Confirm email'
    ];

    public ?string $selectedTemplate = null;
    public int $iteration = 0;

    public function refresh()
    {
        $this->iteration++;
    }
}
