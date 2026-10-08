<?php

namespace FluffyPaws\Migrations\Localization;

/**
 * Provider sign-in (Google, Microsoft, OIDC): button labels and the messages the login page
 * shows when a sign-in does not go through. An app adds `oauth.continue.<key>` for a provider
 * of its own.
 *
 * `oauth.error.failed` covers every refusal that is not the visitor's to fix, a deactivated
 * account included: the password login does not say which it is either.
 */
class LocaleEnglishMigration06 extends LocaleEnglishMigration
{
    public function getResources(): array
    {
        return [
            'oauth.continue.google' => 'Continue with Google',
            'oauth.continue.microsoft' => 'Continue with Microsoft',
            'oauth.or' => 'or',
            'oauth.completing' => 'Signing you in...',
            'oauth.error.cancelled' => 'Sign-in was cancelled.',
            'oauth.error.failed' => "We couldn't sign you in. Please try again.",
            'oauth.error.no-email' => "That account did not share an email address, so we can't sign you in with it.",
            'oauth.error.account-exists' => 'An account with this email address already exists. Log in with your password.',
            'oauth.error.not-accepted' => "We can't create an account for this email address. Contact support if you think that's wrong.",
        ];
    }
}
