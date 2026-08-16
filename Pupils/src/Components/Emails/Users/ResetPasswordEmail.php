<?php

namespace Pupils\Components\Emails\Users;

use Pupils\Components\Services\Localization\Localization;
use SharedPaws\Models\Auth\UserViewModel;
use SharedPaws\Models\Emails\IPlainTextEmail;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;

class ResetPasswordEmail extends BaseComponent implements IPlainTextEmail
{
    public static bool $_noBrowser = true;

    public string $baseUrl = '/';
    public UserViewModel $user;
    public string $verificationCode;

    public function __construct(
        UserViewModel $user,
        string $verificationCode,
        ConfigService $configService,
        private Localization $localization
    ) {
        $this->user = $user;
        $this->verificationCode = $verificationCode;
        $this->baseUrl = $configService->get('baseUrl');
    }

    /**
     * Mirrors the template — including its reuse of the `email.activate.message` key, which the
     * HTML version also uses for the greeting.
     */
    public function text(): string
    {
        $intro = $this->localization->t(
            'email.activate.message',
            ['name' => $this->user->FirstName, 'surname' => $this->user->LastName]
        );
        $action = $this->localization->t('email.reset-password.click-here');
        $link = "{$this->baseUrl}/password/reset/{$this->verificationCode}";

        return "{$intro}\n\n{$action}:\n{$link}\n";
    }
}
