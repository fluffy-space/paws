<?php

namespace FluffyPaws\Services\Auth\OAuth;

/**
 * Any standard OpenID Connect issuer, declared in config with no code:
 *
 *   'auth' => [
 *       'providers' => ['acme'],
 *       'oidc' => [
 *           'acme' => [
 *               'issuer' => 'https://id.acme.example',
 *               'authorizeUrl' => 'https://id.acme.example/authorize',
 *               'tokenUrl' => 'https://id.acme.example/token',
 *               'scopes' => ['openid', 'email'],   // optional, this is the default
 *               'trustEmail' => true,              // optional, default false
 *           ],
 *       ],
 *   ]
 *
 * `trustEmail` says the issuer's `email_verified` claim can be believed. Leave it off for an
 * issuer whose directory admins can set any address: accounts are then confirmed by mail.
 */
class GenericOidcProvider implements IOAuthProvider
{
    public function __construct(private string $key, private array $definition) {}

    public function key(): string
    {
        return $this->key;
    }

    public function authorizeUrl(): string
    {
        return (string) ($this->definition['authorizeUrl'] ?? '');
    }

    public function tokenUrl(): string
    {
        return (string) ($this->definition['tokenUrl'] ?? '');
    }

    public function scopes(): array
    {
        $scopes = $this->definition['scopes'] ?? null;
        return is_array($scopes) && count($scopes) > 0 ? $scopes : ['openid', 'email'];
    }

    public function authorizeParams(): array
    {
        $params = $this->definition['authorizeParams'] ?? null;
        return is_array($params) ? $params : [];
    }

    public function issuerValid(array $claims): bool
    {
        $issuer = (string) ($this->definition['issuer'] ?? '');
        return $issuer !== '' && ($claims['iss'] ?? null) === $issuer;
    }

    public function emailVerified(array $claims): bool
    {
        if (!($this->definition['trustEmail'] ?? false)) {
            return false;
        }
        $verified = $claims['email_verified'] ?? false;
        return $verified === true || $verified === 'true';
    }
}
