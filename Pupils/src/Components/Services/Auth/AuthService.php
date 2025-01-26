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
