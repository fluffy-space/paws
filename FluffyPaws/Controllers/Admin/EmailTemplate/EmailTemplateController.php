<?php

namespace FluffyPaws\Controllers\Admin\EmailTemplate;

use Fluffy\Controllers\BaseController;
use Fluffy\Services\Auth\AuthorizationService;
use FluffyPaws\Security\PawsCapability;
use FluffyPaws\Services\Emails\EmailPreviewContext;
use FluffyPaws\Services\Emails\EmailPreviewRegistry;
use FluffyPaws\Services\Emails\EmailService;
use Viewi\App;

class EmailTemplateController extends BaseController
{
    function __construct(
        protected AuthorizationService $auth,
        protected EmailService $emailService,
        protected App $viewiApp,
        protected EmailPreviewRegistry $registry
    ) {}

    /** GET /api/admin/email-template/templates — key => label map for the dropdown. */
    public function Templates()
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageEmailTemplates)) {
            return $this->Forbidden();
        }
        return $this->registry->labels();
    }

    /** GET /api/admin/email-template/preview/{template} — rendered HTML for the iframe. */
    public function GetPreview(string $template)
    {
        if (!$this->auth->authorizeAdminCapability(PawsCapability::ManageEmailTemplates)) {
            return $this->Forbidden();
        }
        $context = new EmailPreviewContext($this->emailService, $this->viewiApp);
        return $this->registry->render($template, $context);
    }
}
