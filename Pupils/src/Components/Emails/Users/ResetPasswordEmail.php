<?php

namespace Pupils\Components\Emails\Users;

use Pupils\Components\Services\Localization\Localization;
use SharedPaws\Models\Auth\UserViewModel;
use SharedPaws\Models\Emails\IPlainTextEmail;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;
use SharedPaws\Support\UserDisplay;

class ResetPasswordEmail extends BaseComponent implements IPlainTextEmail
{
    public static bool $_noBrowser = true;

    public string $baseUrl = '/';
    public UserViewModel $user;
    public string $verificationCode;

    /**
     * Resolved here, not in the template: a `Class::method()` call inside a Viewi template does
     * not resolve, and an email component that throws takes the whole send down silently — the
     * EmailLog row is never written, so it looks like nothing was ever dispatched.
     */
    public string $userName = '';

    public function __construct(
        UserViewModel $user,
        string $verificationCode,
        ConfigService $configService,
        private Localization $localization
    ) {
        $this->user = $user;
        $this->verificationCode = $verificationCode;
        $this->baseUrl = $configService->get('baseUrl');
        $this->userName = UserDisplay::forUser($user);
    }

    /**
     * Mirrors the template — including its reuse of the `email.activate.message` key, which the
     * HTML version also uses for the greeting.
     */
    public function text(): string
    {
        $intro = $this->localization->t(
            'email.activate.message',
            ['name' => $this->userName, 'surname' => '']
        );
        $action = $this->localization->t('email.reset-password.click-here');
        $link = "{$this->baseUrl}/password/reset/{$this->verificationCode}";

        return "{$intro}\n\n{$action}:\n{$link}\n";
    }
}
