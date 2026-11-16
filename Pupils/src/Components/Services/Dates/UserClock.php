<?php

namespace Pupils\Components\Services\Dates;

use Pupils\Components\Services\Auth\AuthService;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use Viewi\Components\Http\HttpClient;
use Viewi\DI\Singleton;

/**
 * Dates in the signed-in person's time zone, the same text on the server render and in the browser.
 *
 * The zone and its offset table come with the session (UserViewModel::TimeZoneOffsets, built by
 * TimeZones::offsets on the server). Formatting shifts the instant by the offset in force and
 * calls gmdate, so nothing here depends on the browser's own zone data or on the server's default
 * zone. Signed out, or with no zone on the account, everything reads as UTC.
 *
 * Use date() wherever a component showed gmdate() to a person. Machine values (API fields, ISO
 * strings, cache keys) keep gmdate.
 *
 * An account with no zone gets the browser's, reported once (authorization/time-zone); the
 * session is then read again so the same page view already shows local dates.
 */
#[Singleton]
class UserClock
{
    public const UTC = 'UTC';

    /** @var int[][] [[fromUnixSeconds, offsetSeconds], ...] oldest first */
    private array $offsets = [];
    private string $zoneName = 'UTC';
    private bool $reported = false;
    private ?string $browserZone = null;

    public function __construct(private AuthService $auth, private HttpClient $http)
    {
        $this->auth->subscribe(function (UserAuthSessionModel $session) {
            $this->apply($session);
        });
    }

    private function apply(UserAuthSessionModel $session)
    {
        $user = $session->user;
        if (!$session->isAuthenticated || $user === null) {
            $this->zoneName = self::UTC;
            $this->offsets = [];
            return;
        }
        $this->offsets = $user->TimeZoneOffsets;
        if ($user->TimeZone !== null && $user->TimeZone !== '') {
            $this->zoneName = $user->TimeZone;
            return;
        }
        $this->zoneName = self::UTC;
        if (!$session->impersonating) {
            $this->reportBrowserZone();
        }
    }

    /** Browser only, once per page load: hand the account the browser's zone while it has none. */
    private function reportBrowserZone()
    {
        if ($this->reported) {
            return;
        }
        <<<'javascript'
        try {
            $this.browserZone = Intl.DateTimeFormat().resolvedOptions().timeZone || null;
        } catch (e) {
            $this.browserZone = null;
        }
        javascript;
        // A browser that says UTC is usually hiding its zone (privacy mode, a headless test run).
        // Recording it would stop the person's ordinary browser from setting the real one later,
        // and an account with no zone reads as UTC anyway.
        if ($this->browserZone === null || $this->browserZone === self::UTC || $this->browserZone === 'Etc/UTC') {
            return;
        }
        $this->reported = true;
        $this->http->post('/api/authorization/time-zone', ['Zone' => $this->browserZone])
            ->then(function ($response) {
                if ($response['zone'] ?? null) {
                    $this->auth->reset();
                }
            }, function ($error) {});
    }

    /** The account's IANA zone name, or UTC. */
    public function zone(): string
    {
        return $this->zoneName;
    }

    /** True when dates are shown in UTC because the account has no zone. */
    public function isUtc(): bool
    {
        return $this->zoneName === self::UTC;
    }

    /** Seconds east of UTC in the person's zone at that instant. */
    public function offset(int $seconds): int
    {
        $offset = 0;
        $first = true;
        foreach ($this->offsets as $row) {
            if ($first || $row[0] <= $seconds) {
                $offset = $row[1];
                $first = false;
            } else {
                break;
            }
        }
        return $offset;
    }

    /**
     * gmdate() in the person's zone. Formats that print a zone (e, T, O, P, Z, c, r, U) are not
     * supported: they would print UTC. Use zone() or label() for the name.
     */
    public function date(string $format, int $seconds): string
    {
        return gmdate($format, $seconds + $this->offset($seconds));
    }

    /** date() for a timestamp in microseconds. */
    public function dateMicros(string $format, int $micros): string
    {
        return $this->date($format, (int) ($micros / 1000000));
    }

    /**
     * The instant of a wall-clock "Y-m-d H:i:s" read in the person's zone, or null when it does
     * not parse. A time the clocks skip in spring lands an hour later; one they repeat in autumn
     * is taken as the first of the two.
     */
    public function fromWallTime(string $wall): ?int
    {
        $asUtc = strtotime($wall . ' UTC');
        if ($asUtc === false) {
            return null;
        }
        $offset = $this->offset($asUtc);
        $seconds = $asUtc - $offset;
        $again = $this->offset($seconds);
        return $again === $offset ? $seconds : $asUtc - $again;
    }

    /** ' UTC' while the account has no zone, else nothing: the suffix for a time shown on its own. */
    public function suffix(): string
    {
        return $this->zoneName === self::UTC ? ' UTC' : '';
    }

    /** Today's date (Y-m-d) in the person's zone. */
    public function today(): string
    {
        return $this->date('Y-m-d', time());
    }

    /** "Europe/Kyiv" as "Europe/Kyiv time", or "UTC": for a line that says which zone a page uses. */
    public function label(): string
    {
        return $this->zoneName === self::UTC ? self::UTC : str_replace('_', ' ', $this->zoneName) . ' time';
    }
}
