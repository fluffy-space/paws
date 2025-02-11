<?php

namespace Pupils\Components\Emails\Users;

use SharedPaws\Models\Auth\UserViewModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;

class ActivateUserEmail extends BaseComponent
{
    public static bool $_noBrowser = true;

    public string $baseUrl = '/';
    public UserViewModel $user;
    public string $verificationCode;

    public function __construct(UserViewModel $user, string $verificationCode, ConfigService $configService)
    {
        $this->user = $user;
        $this->verificationCode = $verificationCode;
        $this->baseUrl = $configService->get('baseUrl');
    }
}
