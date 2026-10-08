<?php

namespace FluffyPaws\Services\Auth\OAuth;

use Fluffy\Services\Settings\SettingDefinition;
use Fluffy\Services\Settings\SettingsRegistry;

/**
 * Client credentials for the built-in sign-in providers (settings store, group `oauth`), so
 * they are set and rotated on the admin settings page with no redeploy.
 *
 * Having credentials does not switch a provider on: its key must also be listed in config
 * `auth.providers`. An app that lists nothing has no provider sign-in, whatever is stored here.
 *
 * Called once per worker at boot from PawsStartUp::configureServices.
 */
class OAuthSettings
{
    public static function clientIdKey(string $provider): string
    {
        return "oauth.{$provider}.clientId";
    }

    public static function clientSecretKey(string $provider): string
    {
        return "oauth.{$provider}.clientSecret";
    }

    public static function register(): void
    {
        foreach (['google' => 'Google', 'microsoft' => 'Microsoft'] as $key => $name) {
            SettingsRegistry::define(new SettingDefinition(
                key: self::clientIdKey($key),
                type: 'string',
                group: 'oauth',
                label: "{$name} sign-in: client id",
                description: "Used only when '{$key}' is listed in config auth.providers.",
                seedFrom: self::clientIdKey($key),
            ));
            SettingsRegistry::define(new SettingDefinition(
                key: self::clientSecretKey($key),
                type: 'string',
                group: 'oauth',
                label: "{$name} sign-in: client secret",
                description: "Secret. Used only when '{$key}' is listed in config auth.providers.",
                seedFrom: self::clientSecretKey($key),
            ));
        }
    }
}
