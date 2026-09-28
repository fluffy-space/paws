<?php

namespace FluffyPaws\Services\Auth;

/**
 * Lets an app react when a user confirms their email address (AuthorizationController::ConfirmEmail).
 * Every registered implementation is resolved with getAll() and called once per confirmation,
 * only when the account was unconfirmed before the click.
 *
 * Needed for mail that must not reach an unverified address, such as a welcome: the app holds it
 * back at signup and sends it from here.
 */
interface IEmailConfirmedListener
{
    public function onEmailConfirmed(int $userId): void;
}
