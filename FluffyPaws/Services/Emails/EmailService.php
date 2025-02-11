<?php

namespace FluffyPaws\Services\Emails;

use FluffyPaws\Services\Localization\LocalizationService;
use Exception;
use Fluffy\Swoole\Task\TaskManager;
use Pupils\Components\Emails\Users\ActivateUserEmail;
use Pupils\Components\Emails\Users\ResetPasswordEmail;
use ReflectionException;
use SharedPaws\Models\Auth\UserViewModel;
use Viewi\App;

class EmailService
{
    public function __construct(private TaskManager $tasks, private App $viewiApp, private EmailConnector $connector, private LocalizationService $localization)
    {
    }

    // ActivateUserEmail
    public function dispatchUserActivateEmail(UserViewModel $user, string $verificationCode)
    {
        $this->tasks->dispatch($this->sendUserActivateEmail(...), $user, $verificationCode);
    }

    public function sendUserActivateEmail(UserViewModel $user, $verificationCode)
    {
        $html = $this->getUserActivateEmail($user, $verificationCode);
        $this->connector->send($user->Email, $this->localization->localize('email.activate.title'), $html->body, "{$user->FirstName} {$user->LastName}", $this->localization->localize('email.activate.title'));
    }

    public function getUserActivateEmail(UserViewModel $user, $verificationCode)
    {
        $html = $this->viewiApp->engine()->render(ActivateUserEmail::class, ['user' => $user, 'verificationCode' => $verificationCode]);
        return $html;
    }

    // ResetPasswordEmail
    public function dispatchPasswordResetEmail(UserViewModel $user, string $verificationCode)
    {
        $this->tasks->dispatch($this->sendPasswordResetEmail(...), $user, $verificationCode);
    }

    public function sendPasswordResetEmail(UserViewModel $user, $verificationCode)
    {
        $html = $this->getSendPasswordResetEmail($user, $verificationCode);
        $this->connector->send($user->Email, $this->localization->localize('email.reset-password.title'), $html->body, "{$user->FirstName} {$user->LastName}", $this->localization->localize('email.reset-password.title'));
    }

    public function getSendPasswordResetEmail(UserViewModel $user, $verificationCode)
    {
        return $this->viewiApp->engine()->render(ResetPasswordEmail::class, ['user' => $user, 'verificationCode' => $verificationCode]);
    }
}
