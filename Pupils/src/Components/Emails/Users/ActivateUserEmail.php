<?php

namespace Pupils\Components\Emails\Users;

use Pupils\Components\Services\Localization\Localization;
use SharedPaws\Models\Auth\UserViewModel;
use SharedPaws\Models\Emails\IPlainTextEmail;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;
use SharedPaws\Support\UserDisplay;

class ActivateUserEmail extends BaseComponent implements IPlainTextEmail
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
     * The same wording and the same localization keys as the template, so the two versions of this
     * email say the same thing. `Localization` — not the backend LocalizationService — because that
     * is what the template's `t()` resolves to, and the two stores must not disagree.
     */
    public function text(): string
    {
        $confirm = $this->localization->t(
            'email.activate.please-confirm',
            ['name' => $this->userName, 'surname' => '']
        );
        $action = $this->localization->t('email.activate.click-here-to-activate');
        $link = "{$this->baseUrl}/account/confirm/{$this->verificationCode}";

        return "{$confirm}\n\n{$action}:\n{$link}\n";
    }
}
