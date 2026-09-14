<?php

namespace SharedPaws\Support;

/**
 * How a user is named on screen and in email.
 *
 * `FirstName`/`LastName` are optional at registration, so a display site that concatenates the two
 * raw renders a bare space for an account that never supplied them. Every site calls this instead.
 *
 * A stateless static helper rather than a method on the models: `UserViewModel`, `UserModel` and
 * `Fluffy\Models\Users\UserModel` are plain data and stay that way. Shaped like
 * {@see \Components\Support\UtmUrl} — only the simple string builtins are dependably available in
 * transpiled Viewi components, so this uses trim/explode and nothing cleverer.
 */
class UserDisplay
{
    /**
     * First+last, else the local part of the email address, else 'User'.
     *
     * Never returns an empty string, so callers can take a first character for an avatar without
     * guarding.
     *
     * `$user` is deliberately untyped: any user model with `FirstName`, `LastName`, `Email` and
     * `UserName`, or null. The three that qualify live in different namespaces and two are both
     * called `UserModel` — Viewi has no namespaces, so importing them to write a union collides
     * in the bundle, and a union is not worth having here anyway.
     *
     * `??` is deliberate on `UserName`: it is a non-nullable typed property that the registration
     * mapper never sets, so reading it directly can throw on an uninitialised value.
     *
     * **Not called `name()`.** Viewi emits a static as `UserDisplay.name = function ...`, and
     * `Function.name` is non-writable — that assignment throws in strict mode once bundled, while
     * PHP and SSR are perfectly happy. Avoid `name`, `length` and `caller` for statics here.
     */
    public static function forUser($user): string
    {
        if ($user === null) {
            return 'User';
        }
        $name = trim(($user->FirstName ?? '') . ' ' . ($user->LastName ?? ''));
        if ($name !== '') {
            return $name;
        }
        // UserName is the email — AuthorizationService::registerUser sets it to Email (or Phone
        // when there is no email), so this is a fallback for the address, not a separate handle.
        $email = $user->Email ?? '';
        if ($email === '') {
            $email = $user->UserName ?? '';
        }
        if ($email !== '') {
            return explode('@', $email)[0];
        }
        return 'User';
    }
}
