<?php

namespace Pupils\Components\Views\Admin\EmailTemplates;

use Pupils\Components\Guards\HasCapability;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;

#[Middleware([[HasCapability::class, 'ManageEmailTemplates']])]
class PreviewEmailPage extends BaseComponent
{
    /** key => label, fetched from the server-side EmailPreviewRegistry. */
    public array $templates = [];

    public ?string $selectedTemplate = null;
    public int $iteration = 0;

    public function __construct(private HttpClient $http)
    {
    }

    public function mounted()
    {
        $this->http->get('/api/admin/email-template/templates')
            ->then(function ($templates) {
                $this->templates = $templates;
            });
    }

    public function refresh()
    {
        $this->iteration++;
    }
}
