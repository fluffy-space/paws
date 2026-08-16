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
    public function __construct(private TaskManager $tasks, private App $viewiApp, private EmailLogService $emailLog, private LocalizationService $localization, private EmailRenderer $renderer)
    {
    }

    // ActivateUserEmail
    public function dispatchUserActivateEmail(UserViewModel $user, string $verificationCode)
    {
        $this->tasks->dispatch($this->sendUserActivateEmail(...), $user, $verificationCode);
    }

    public function sendUserActivateEmail(UserViewModel $user, $verificationCode)
    {
        $email = $this->getUserActivateEmail($user, $verificationCode);
        // The text part comes from the component's own text(), not from the subject line — sending
        // the subject as the text body is what used to leave this email with no confirmation link
        // in its text/plain alternative at all.
        $this->emailLog->send('confirm-email', $user->Email, $this->localization->localize('email.activate.title'), $email->html, "{$user->FirstName} {$user->LastName}", $email->text);
    }

    public function getUserActivateEmail(UserViewModel $user, $verificationCode): RenderedEmail
    {
        return $this->renderer->render(ActivateUserEmail::class, ['user' => $user, 'verificationCode' => $verificationCode]);
    }

    // ResetPasswordEmail
    public function dispatchPasswordResetEmail(UserViewModel $user, string $verificationCode)
    {
        $this->tasks->dispatch($this->sendPasswordResetEmail(...), $user, $verificationCode);
    }

    public function sendPasswordResetEmail(UserViewModel $user, $verificationCode)
    {
        $email = $this->getSendPasswordResetEmail($user, $verificationCode);
        // As above — the reset link has to survive into the text part.
        $this->emailLog->send('reset-password', $user->Email, $this->localization->localize('email.reset-password.title'), $email->html, "{$user->FirstName} {$user->LastName}", $email->text);
    }

    public function getSendPasswordResetEmail(UserViewModel $user, $verificationCode): RenderedEmail
    {
        return $this->renderer->render(ResetPasswordEmail::class, ['user' => $user, 'verificationCode' => $verificationCode]);
    }
}
