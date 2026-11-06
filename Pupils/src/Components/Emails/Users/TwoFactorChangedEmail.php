<?php

namespace Pupils\Components\Emails\Users;

use SharedPaws\Models\Auth\UserViewModel;
use SharedPaws\Models\Emails\IPlainTextEmail;
use SharedPaws\Support\UserDisplay;
use Viewi\Components\BaseComponent;
use Viewi\Components\Config\ConfigService;

/**
 * Two-factor sign-in was turned on or off. Sent every time, so a change the owner did not make
 * is noticed: whoever made it already had the password.
 */
class TwoFactorChangedEmail extends BaseComponent implements IPlainTextEmail
{
    public static bool $_noBrowser = true;

    public string $baseUrl = '/';
    public string $userName = '';
    public bool $turnedOn = false;
    /** Turned off by an administrator (a reset for someone locked out), not by the user. */
    public bool $byAdmin = false;

    public function __construct(UserViewModel $user, bool $turnedOn, bool $byAdmin, ConfigService $configService)
    {
        $this->turnedOn = $turnedOn;
        $this->byAdmin = $byAdmin;
        $this->baseUrl = $configService->get('baseUrl');
        $this->userName = UserDisplay::forUser($user);
    }

    public function text(): string
    {
        $lines = ["Hi {$this->userName},", ''];
        if ($this->turnedOn) {
            $lines[] = 'Two-factor sign-in is now on for your account. Signing in will ask for a code from your authenticator app.';
            $lines[] = '';
            $lines[] = "If you did not do this, reset your password now: {$this->baseUrl}/reset-password";
        } else if ($this->byAdmin) {
            $lines[] = 'An administrator turned off two-factor sign-in for your account, which is how we help someone who lost their authenticator app. You have been signed out everywhere.';
            $lines[] = '';
            $lines[] = "Sign in with your password, then turn it on again on your account page: {$this->baseUrl}/app/account";
            $lines[] = '';
            $lines[] = "If you did not ask for this, reset your password now: {$this->baseUrl}/reset-password";
        } else {
            $lines[] = 'Two-factor sign-in is now off for your account. Your password alone signs you in.';
            $lines[] = '';
            $lines[] = "If you did not do this, reset your password now: {$this->baseUrl}/reset-password";
        }
        return implode("\n", $lines) . "\n";
    }
}
