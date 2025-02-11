<?php

namespace FluffyPaws\Controllers\Admin\EmailTemplate;

use Fluffy\Controllers\BaseController;
use Fluffy\Services\Auth\AuthorizationService;
use Fluffy\Services\UtilsService;
use FluffyPaws\Services\Emails\EmailService;
use SharedPaws\Models\Auth\UserViewModel;

class EmailTemplateController extends BaseController
{
    function __construct(
        protected AuthorizationService $auth,
        protected EmailService $emailService
    ) {}

    public function GetPreview(string $template)
    {
        if (!$this->auth->authorizeAdminRequest()) {
            return $this->Forbidden();
        }

        switch ($template) {
            case 'reset-password': {
                    $user = $this->generateUserModel();
                    $code = UtilsService::randomString(32);
                    $html = $this->emailService->getSendPasswordResetEmail($user, $code);
                    return $html->body;
                }
            case 'confirm-email': {
                    $user = $this->generateUserModel();
                    $code = UtilsService::randomString(32);
                    $html = $this->emailService->getUserActivateEmail($user, $code);
                    return $html->body;
                }
            default:
                return null;
        }
        return null;
    }

    protected function generateUserModel(): UserViewModel
    {
        $user = new UserViewModel();
        $user->FirstName = 'Miki';
        $user->LastName = 'Darkness';
        $user->Active = true;
        $user->Email = 'miki@testing.com';
        $user->EmailConfirmed = true;
        $user->UserName = $user->Email;
        return $user;
    }
}
