<?php

namespace FluffyPaws\Services\Auth\OAuth;

/**
 * Microsoft identity platform, `common` endpoint: personal accounts and any work tenant.
 */
class MicrosoftProvider implements IOAuthProvider
{
    /** The tenant every personal (outlook.com, hotmail.com, live.com) account lives in. */
    private const CONSUMER_TENANT = '9188040d-6c67-4c5b-b112-36a304b66dad';

    public function key(): string
    {
        return 'microsoft';
    }

    public function authorizeUrl(): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize';
    }

    public function tokenUrl(): string
    {
        return 'https://login.microsoftonline.com/common/oauth2/v2.0/token';
    }

    public function scopes(): array
    {
        return ['openid', 'email'];
    }

    public function authorizeParams(): array
    {
        return ['prompt' => 'select_account'];
    }

    public function issuerValid(array $claims): bool
    {
        // With `common` the issuer names the signing-in account's own tenant, so there is no
        // single value to compare against: it must be that tenant's v2.0 issuer.
        $tenant = (string) ($claims['tid'] ?? '');
        if (!preg_match('/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $tenant)) {
            return false;
        }
        return ($claims['iss'] ?? null) === "https://login.microsoftonline.com/{$tenant}/v2.0";
    }

    public function emailVerified(array $claims): bool
    {
        // In a work tenant the `email` claim is whatever the tenant's admin typed into the
        // directory: nobody proved it. Treating it as verified lets a tenant admin sign in as
        // any address they like. Only a personal account's address is one Microsoft checked,
        // or one a tenant has marked domain-owner-verified (`xms_edov`).
        if (strtolower((string) ($claims['tid'] ?? '')) === self::CONSUMER_TENANT) {
            return true;
        }
        $edov = $claims['xms_edov'] ?? false;
        return $edov === true || $edov === 'true' || $edov === 1 || $edov === '1';
    }
}
