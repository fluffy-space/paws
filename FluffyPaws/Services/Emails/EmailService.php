<?php

namespace FluffyPaws\Services\Emails;

use FluffyPaws\Services\Localization\LocalizationService;
use Exception;
use Fluffy\Swoole\Task\TaskManager;
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

    // NewMemberEmail
    /**
     * 
     * @param UserViewModel $user 
     * @param EmailAttachment[] $attachments 
     * @return void 
     */
    public function dispatchNewMemberEmail(UserViewModel $user, array $attachments)
    {
        $this->tasks->dispatch($this->sendNewMemberEmail(...), $user, $attachments);
    }

    /**
     * 
     * @param UserViewModel $user 
     * @param EmailAttachment[] $attachments 
     * @return void 
     * @throws Exception 
     * @throws ReflectionException 
     */
    public function sendNewMemberEmail(UserViewModel $user, array $attachments)
    {
        $html = $this->getNewMemberEmail($user);
        $this->connector->send($user->Email, $this->localization->localize('email.new-member.title'), $html->body, "{$user->FirstName} {$user->LastName}", $this->localization->localize('email.new-member.title'), $attachments);
    }

    public function getNewMemberEmail(UserViewModel $user)
    {
        return $this->viewiApp->engine()->render(NewMemberEmail::class, ['user' => $user]);
    }

    // NewMemberAdminEmail
    /**
     * 
     * @param UserViewModel $user 
     * @return void 
     */
    public function dispatchNewMemberAdminEmail(UserViewModel $user, string $email)
    {
        $this->tasks->dispatch($this->sendNewMemberAdminEmail(...), $user, $email);
    }

    /**
     * 
     * @param UserViewModel $user 
     * @return void 
     * @throws Exception 
     * @throws ReflectionException 
     */
    public function sendNewMemberAdminEmail(UserViewModel $user, string $email)
    {
        $html = $this->getNewMemberAdminEmail($user);
        $this->connector->send($email, $this->localization->localize('email.new-member-admin.title'), $html->body, $this->localization->localize('email.new-member-admin.manager-name'), $this->localization->localize('email.new-member-admin.title'));
    }

    public function getNewMemberAdminEmail(UserViewModel $user)
    {
        return $this->viewiApp->engine()->render(NewMemberAdminEmail::class, ['user' => $user]);
    }
}
