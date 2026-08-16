<?php

namespace Pupils\Components\Emails\Users;

use Pupils\Components\Services\Localization\Localization;
use SharedPaws\Models\Auth\UserViewModel;
use SharedPaws\Models\Emails\IPlainTextEmail;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;

class ActivateUserEmail extends BaseComponent implements IPlainTextEmail
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
     * The same wording and the same localization keys as the template, so the two versions of this
     * email say the same thing. `Localization` — not the backend LocalizationService — because that
     * is what the template's `t()` resolves to, and the two stores must not disagree.
     */
    public function text(): string
    {
        $confirm = $this->localization->t(
            'email.activate.please-confirm',
            ['name' => $this->user->FirstName, 'surname' => $this->user->LastName]
        );
        $action = $this->localization->t('email.activate.click-here-to-activate');
        $link = "{$this->baseUrl}/account/confirm/{$this->verificationCode}";

        return "{$confirm}\n\n{$action}:\n{$link}\n";
    }
}
