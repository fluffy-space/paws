<?php

namespace Pupils\Components\Services\Auth;

use Viewi\Components\Environment\ClientTimer;
use Viewi\Components\Http\HttpClient;
use Viewi\DI\Singleton;

/**
 * The signed "form issued at" stamp that register / reset-password must send (AuthFormGuard on
 * the server refuses a missing, forged, too-young or day-old one).
 *
 * The waiting happens HERE, so a person can never trip the timing rule: a submit before the
 * stamp arrived fetches it first, a submit sooner than the server's minimum age is held until it
 * is old enough, and a stamp from a tab left open overnight is swapped for a new one. Only a
 * script posting straight to the API, without this page, gets refused for timing.
 */
#[Singleton]
class FormTokenService
{
    private const REFRESH_AFTER = 20 * 3600;

    private ?string $token = null;
    private float $receivedAt = 0;
    private int $minSeconds = 3;
    private bool $loading = false;
    private array $waiting = [];

    public function __construct(private HttpClient $http) {}

    /** Start fetching as the form renders, so a normal submit never waits. Client-only. */
    public function prefetch(): void
    {
        if ($this->token === null && !$this->loading) {
            $this->load();
        }
    }

    /** Calls $send with a usable stamp, or with null when it could not be fetched at all. */
    public function whenReady(callable $send): void
    {
        if ($this->token !== null && microtime(true) - $this->receivedAt < self::REFRESH_AFTER) {
            $this->sendWhenOldEnough($send);
            return;
        }
        $this->token = null;
        $this->waiting[] = $send;
        if (!$this->loading) {
            $this->load();
        }
    }

    /** After a refused submit: the next one fetches a fresh stamp. */
    public function discard(): void
    {
        $this->token = null;
    }

    private function load(): void
    {
        $this->loading = true;
        $this->http->get('/api/authorization/form-token')->then(
            function ($response) {
                $this->token = $response['FormToken'];
                $this->minSeconds = (int) $response['MinSeconds'];
                $this->receivedAt = microtime(true);
                $this->flush(true);
            },
            function () {
                $this->flush(false);
            }
        );
    }

    private function flush(bool $ok): void
    {
        $this->loading = false;
        $waiting = $this->waiting;
        $this->waiting = [];
        foreach ($waiting as $send) {
            if ($ok) {
                $this->sendWhenOldEnough($send);
            } else {
                $send(null);
            }
        }
    }

    private function sendWhenOldEnough(callable $send): void
    {
        // Half a second over the minimum: the server's clock started at issue, ours at receipt.
        $wait = $this->receivedAt + $this->minSeconds + 0.5 - microtime(true);
        $token = $this->token;
        if ($wait > 0) {
            ClientTimer::setTimeoutStatic(fn() => $send($token), (int) ceil($wait * 1000));
        } else {
            $send($token);
        }
    }
}
