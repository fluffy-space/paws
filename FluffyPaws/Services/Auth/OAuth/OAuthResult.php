<?php

namespace FluffyPaws\Services\Auth\OAuth;

class OAuthResult
{
    public bool $Success = false;
    /**
     * Why it failed, as the login page will say it: cancelled, failed, no-email, account-exists,
     * not-accepted. Never a rule name, and a deactivated account is plain `failed`, as the
     * password login does not tell it apart either.
     */
    public ?string $Error = null;
    /** True when this sign-in created the account. */
    public bool $Created = false;
    /** Same-site path the visitor asked to come back to, if any. */
    public ?string $ReturnTo = null;

    public static function fail(string $error, ?string $returnTo = null): self
    {
        $result = new self();
        $result->Error = $error;
        $result->ReturnTo = $returnTo;
        return $result;
    }
}
