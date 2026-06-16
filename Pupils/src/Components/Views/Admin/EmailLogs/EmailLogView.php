<?php

namespace Pupils\Components\Views\Admin\EmailLogs;

use Pupils\Components\Guards\HasCapability;
use SharedPaws\Models\Emails\EmailLogModel;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;

/**
 * Read-only detail of a single EmailLog row, reached from the list's row action
 * (/admin/email-log/{id}). Shows metadata + the stored HTML body in an iframe
 * (the body endpoint returns a placeholder when nothing was stored, i.e. in prod).
 */
#[Middleware([[HasCapability::class, 'ManageEmailTemplates']])]
class EmailLogView extends BaseComponent
{
    public ?EmailLogModel $item = null;

    public function __construct(
        public int $id,
        private HttpClient $http
    ) {}

    public function init()
    {
        $this->http->get("/api/admin/email-log/{$this->id}")
            ->then(function ($item) {
                $this->item = $item;
            }, function () {
                // error
            });
    }

    public function formatTime(?int $micro): string
    {
        if (!$micro) {
            return '—';
        }
        return date('Y-m-d H:i:s', (int)($micro / 1000000));
    }
}
