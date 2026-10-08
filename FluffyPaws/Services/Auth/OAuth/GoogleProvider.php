<?php

namespace FluffyPaws\Services\Auth\OAuth;

class GoogleProvider implements IOAuthProvider
{
    public function key(): string
    {
        return 'google';
    }

    public function authorizeUrl(): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth';
    }

    public function tokenUrl(): string
    {
        return 'https://oauth2.googleapis.com/token';
    }

    public function scopes(): array
    {
        return ['openid', 'email'];
    }

    public function authorizeParams(): array
    {
        // Always show the account chooser: a person with a work and a personal account must be
        // able to pick, and a silent pass through whichever one is active picks for them.
        return ['prompt' => 'select_account'];
    }

    public function issuerValid(array $claims): bool
    {
        return in_array($claims['iss'] ?? null, ['https://accounts.google.com', 'accounts.google.com'], true);
    }

    public function emailVerified(array $claims): bool
    {
        $verified = $claims['email_verified'] ?? false;
        return $verified === true || $verified === 'true';
    }
}
