<?php

namespace Pupils\Components\Services\Auth;

use SharedPaws\Models\Auth\UserAuthSessionModel;
use Viewi\Components\Callbacks\Subscriber;
use Viewi\Components\Callbacks\Subscription;
use Viewi\Components\Http\HttpClient;
use Viewi\DI\Singleton;

#[Singleton]
class AuthService
{
    private Subscriber $userSubscriber;
    private bool $activated = false;
    private ?UserAuthSessionModel $userSession = null;
    private array $onceQueue = [];

    public function __construct(private HttpClient $http)
    {
        $this->userSubscriber = new Subscriber();
    }

    public function reset()
    {
        $this->activated = false;
        $this->userSession = null;
        $this->fetchUser();
    }

    public function subscribe(callable $callback): Subscription
    {
        $this->fetchUser();
        return $this->userSubscriber->subscribe($callback);
    }

    /**
     * May this session use the member app at all?
     *
     * False only for a genuinely deactivated account — one an admin switched off, which reads as
     * `!Active && EmailConfirmed`. An unconfirmed signup is `Active=false` too (registration
     * creates it that way; only confirmation flips it), but it is mid-signup, not disabled, and
     * must be let in: the actions that matter are gated server-side instead.
     *
     * Lives here so every page guard shares one definition. Two of them had their own copy of
     * `!$user->Active`, and each one silently locked new signups out of whatever it protected.
     */
    public function canUseApp(?UserAuthSessionModel $session): bool
    {
        if ($session === null || $session->user === null) {
            return false;
        }
        return $session->user->Active || !$session->user->EmailConfirmed;
    }

    public function isAuthorized(callable $callback)
    {
        $this->fetchUser();
        $this->once(function () use ($callback) {
            $callback($this->userSession->isAuthenticated);
        });
    }

    public function getUserSession(callable $callback)
    {
        $this->fetchUser();
        $this->once(function () use ($callback) {
            $callback($this->userSession);
        });
    }

    private function once(callable $callback)
    {
        if ($this->userSession !== null) {
            $callback();
        } else {
            $this->onceQueue[] = $callback;
        }
    }

    private function fetchUser()
    {
        if (!$this->activated) {
            $this->activated = true;
            $this->http->get('/api/authorization/me')
                ->then(function (UserAuthSessionModel $response) {
                    $this->userSession = $response;
                    $this->userSubscriber->publish($this->userSession);
                    $this->resolveOnce();
                }, function ($error) {
                    $this->userSession = new UserAuthSessionModel();
                    $this->userSubscriber->publish($this->userSession);
                    $this->resolveOnce();
                });
        }
    }

    private function resolveOnce()
    {
        foreach ($this->onceQueue as $callback) {
            $callback();
        }
        $this->onceQueue = [];
    }
}
