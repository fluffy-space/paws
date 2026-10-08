<?php

namespace FluffyPaws\Services\Auth;

use DotDi\DependencyInjection\Container;

/**
 * Tells every IEmailConfirmedListener that an account's address is now confirmed.
 *
 * One place, because there are two ways to get there: the click in the confirmation mail
 * (AuthorizationController::ConfirmEmail) and a provider sign-in whose address the provider
 * vouches for (OAuthService).
 */
class EmailConfirmedNotifier
{
    public function __construct(private Container $container) {}

    public function notify(int $userId): void
    {
        /** @var IEmailConfirmedListener[] $listeners */
        $listeners = $this->container->serviceProvider->getAll(IEmailConfirmedListener::class);
        foreach ($listeners as $listener) {
            try {
                $listener->onEmailConfirmed($userId);
            } catch (\Throwable $e) {
                // The address is confirmed either way; a listener's failure must not fail the caller.
                echo '[ConfirmEmail] listener ' . $listener::class . ' failed for user ' . $userId . ': ' . $e->getMessage() . PHP_EOL;
            }
        }
    }
}
