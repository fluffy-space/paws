<?php

namespace FluffyPaws\Services\Emails;

use Fluffy\Services\UtilsService;
use SharedPaws\Models\Auth\UserViewModel;
use Viewi\App;

/**
 * Per-request rendering context handed to email-preview renderers. Carries the
 * request-scoped services a renderer might need (EmailService, the Viewi app) plus
 * factories for throwaway demo data, so renderers stay pure closures and the
 * registry never has to capture scoped services at boot.
 */
class EmailPreviewContext
{
    public function __construct(
        public EmailService $emailService,
        public App $viewiApp,
    ) {
    }

    public function demoUser(): UserViewModel
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

    public function demoCode(): string
    {
        return UtilsService::randomString(32);
    }
}
