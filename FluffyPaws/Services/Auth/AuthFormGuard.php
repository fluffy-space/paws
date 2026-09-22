<?php

namespace FluffyPaws\Services\Auth;

use Fluffy\Domain\Configuration\Config;
use Fluffy\Swoole\RateLimit\IRateLimitService;

/**
 * Bot defences for the public forms that make us send an email to an address the caller typed:
 * register (activation) and reset-password.
 *
 * Those forms are the target of "subscription bombing" bots: they sign a victim's address up on
 * any site that will mail it, then ask for a password reset for a second mail. The accounts cost
 * us nothing; the mail does - every unwanted one is a spam report against our sending domain.
 * So the checks are about the mail, and they are cheap on purpose:
 *
 * - a honeypot field no person sees, which form-filling bots fill in;
 * - a signed "form issued at" token: a script that posts straight to the API has none, and one
 *   that loads the form and submits it within a couple of seconds is not a person typing;
 * - Gmail addresses with dots sprinkled through them - Gmail ignores dots, so every variant
 *   reaches the same victim, and people do not register "ma.l.a.k.i.a.v.1.2";
 * - a per-network and a site-wide cap on these mails, so whatever gets past the rest cannot
 *   turn us into a mail cannon.
 *
 * Everything is tunable under `auth` in configs/app.php; absent keys use the defaults below.
 */
class AuthFormGuard
{
    private const TOKEN_MAX_AGE = 24 * 3600;

    public function __construct(private Config $config, private IRateLimitService $rateLimit) {}

    private function setting(string $key, int $default): int
    {
        return (int) ($this->config->values['auth'][$key] ?? $default);
    }

    /** How old a stamp must be; the form waits this long itself, so only scripts ever trip it. */
    public function minSeconds(): int
    {
        return $this->setting('formMinSeconds', 3);
    }

    /** "<issued unix seconds>.<signature>" - handed to the form when it renders. */
    public function issueToken(): string
    {
        $issued = (string) time();
        return $issued . '.' . $this->sign($issued);
    }

    private function sign(string $issued): string
    {
        return substr(hash_hmac('sha256', 'auth-form|' . $issued, $this->config->values['hashSalt']), 0, 24);
    }

    /**
     * Why this submission looks automated, or null when it does not. The reason is for the log
     * only - callers answer without it, so a bot cannot tell which rule it tripped.
     */
    public function botReason(?string $honeypot, ?string $token): ?string
    {
        if ($honeypot !== null && trim($honeypot) !== '') {
            return 'honeypot';
        }
        $parts = explode('.', (string) $token);
        if (count($parts) !== 2 || !ctype_digit($parts[0]) || !hash_equals($this->sign($parts[0]), $parts[1])) {
            return 'no-token';
        }
        $age = time() - (int) $parts[0];
        if ($age < $this->minSeconds()) {
            return 'too-fast';
        }
        if ($age > self::TOKEN_MAX_AGE) {
            return 'stale-token';
        }
        return null;
    }

    /**
     * A Gmail address with more dots in its name than a person would type. Gmail delivers
     * "a.b.c.d@gmail.com" to "abcd@gmail.com", which is what makes the dotted variants useful to
     * a bot and useless to anyone else.
     */
    public function dottedGmail(?string $email): bool
    {
        $at = strrpos((string) $email, '@');
        if ($at === false) {
            return false;
        }
        $domain = strtolower(substr($email, $at + 1));
        if ($domain !== 'gmail.com' && $domain !== 'googlemail.com') {
            return false;
        }
        $name = explode('+', substr($email, 0, $at), 2)[0];
        return substr_count($name, '.') > $this->setting('gmailMaxDots', 3);
    }

    /**
     * Take one mail from the budget of the caller's network (/24 for IPv4, /64 for IPv6, since a
     * bot rotates addresses inside a range it rents) and then from the site-wide one. False
     * means over budget: send nothing.
     */
    public function takeMailBudget(string $ip): bool
    {
        return $this->rateLimit->limit('auth-mail:net:' . self::network($ip), $this->setting('mailPerNetworkHour', 20), 3600)
            && $this->rateLimit->limit('auth-mail:all', $this->setting('mailPerHour', 200), 3600);
    }

    private static function network(string $ip): string
    {
        $packed = @inet_pton($ip);
        if ($packed === false) {
            return $ip;
        }
        $prefix = strlen($packed) === 4 ? substr($packed, 0, 3) . "\0" : substr($packed, 0, 8) . str_repeat("\0", 8);
        return inet_ntop($prefix);
    }

    public static function log(string $form, string $reason, string $ip, ?string $email): void
    {
        echo '[AuthGuard] refused form=' . $form . ' reason=' . $reason . ' ip=' . $ip . ' email=' . $email . PHP_EOL;
    }
}
