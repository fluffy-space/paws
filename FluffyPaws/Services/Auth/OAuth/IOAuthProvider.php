<?php

namespace FluffyPaws\Services\Auth\OAuth;

/**
 * A sign-in provider that speaks OpenID Connect's authorization code flow.
 *
 * Paws ships Google and Microsoft; an app adds its own with
 * `addScoped(IOAuthProvider::class, MyProvider::class)`, or declares a standard issuer in config
 * (`auth.oidc`, see GenericOidcProvider). Registering a provider does not switch it on: it is
 * live only when its key is listed in config `auth.providers` AND it has a client id and secret
 * (OAuthService::enabled).
 */
interface IOAuthProvider
{
    /** Stable key: the route segment, the config entry and UserIdentity.Provider. */
    public function key(): string;

    public function authorizeUrl(): string;

    public function tokenUrl(): string;

    /** @return string[] `openid` and `email` at least; ask for nothing the app does not use. */
    public function scopes(): array;

    /** Extra query parameters for the authorize request, e.g. ['prompt' => 'select_account']. */
    public function authorizeParams(): array;

    /** Is the ID token's `iss` this provider's? */
    public function issuerValid(array $claims): bool;

    /**
     * Does the provider vouch that the person owns the `email` in these claims?
     *
     * This decides two things, so answer false when unsure: a new account is created already
     * confirmed, and the sign-in may attach to an existing account with the same address.
     * False still lets a new account be created; it then confirms by mail like any other.
     */
    public function emailVerified(array $claims): bool;
}
