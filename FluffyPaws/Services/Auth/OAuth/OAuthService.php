<?php

namespace FluffyPaws\Services\Auth\OAuth;

use DotDi\DependencyInjection\Container;
use Fluffy\Data\Entities\Auth\UserEntity;
use Fluffy\Data\Entities\Auth\UserEntityMap;
use Fluffy\Data\Entities\Auth\UserIdentityEntity;
use Fluffy\Data\Entities\Auth\UserIdentityEntityMap;
use Fluffy\Data\Mapper\IMapper;
use Fluffy\Data\Repositories\UserIdentityRepository;
use Fluffy\Data\Repositories\UserRepository;
use Fluffy\Domain\Configuration\Config;
use Fluffy\Domain\Message\HttpContext;
use Fluffy\Services\Auth\AuthorizationService;
use Fluffy\Services\Settings\SettingsService;
use Fluffy\Services\UtilsService;
use FluffyPaws\Services\Auth\AuthFormGuard;
use FluffyPaws\Services\Auth\EmailConfirmedNotifier;
use FluffyPaws\Services\Emails\EmailService;
use SharedPaws\Models\Auth\UserViewModel;
use Swoole\Coroutine\Http\Client;

/**
 * Sign up / sign in through an OpenID Connect provider (authorization code flow).
 *
 * Off unless the app asks for it: a provider is live only when its key is listed in config
 * `auth.providers` and it has a client id and secret. With nothing listed, start() and
 * complete() refuse everything and the app behaves as if this class did not exist.
 *
 * The visitor's browser talks to the provider only by being redirected there after they click
 * our own link. No provider script, image or cookie is ever put on a page.
 *
 * Kept from the provider: its id for the person (UserIdentity.Subject), and the email address
 * when a new account is created. The ID token is read once and dropped; no access or refresh
 * token is requested for later use or stored.
 */
class OAuthService
{
    /** Carries state, nonce and the return path across the redirect. Signed, HttpOnly. */
    public const COOKIE_NAME = 'OAUTH';
    /** Only the two OAuth endpoints ever see the cookie. */
    public const COOKIE_PATH = '/api/authorization/oauth/';
    /** How long a visitor has to finish at the provider. */
    private const FLOW_LIFETIME = 600;
    /** Tolerated clock difference with the provider when reading `exp`. */
    private const CLOCK_SKEW = 60;

    /** @var array<string, IOAuthProvider>|null */
    private ?array $providers = null;

    public function __construct(
        private Config $config,
        private ?HttpContext $httpContext,
        private AuthorizationService $auth,
        private UserRepository $users,
        private UserIdentityRepository $identities,
        private SettingsService $settings,
        private AuthFormGuard $formGuard,
        private EmailService $emailService,
        private EmailConfirmedNotifier $confirmedNotifier,
        private IMapper $mapper,
        private Container $container
    ) {}

    // --- which providers are on ----------------------------------------------

    /**
     * Keys of the providers a visitor can use right now, in the order config lists them.
     * @return string[]
     */
    public function enabledKeys(): array
    {
        $keys = [];
        foreach ($this->listed() as $key) {
            if ($this->enabled($key) !== null) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /** The provider behind `$key`, or null when it is unknown, not listed, or has no credentials. */
    public function enabled(string $key): ?IOAuthProvider
    {
        if (!in_array($key, $this->listed(), true)) {
            return null;
        }
        $provider = $this->providers()[$key] ?? null;
        if ($provider === null || $provider->authorizeUrl() === '' || $provider->tokenUrl() === '') {
            return null;
        }
        return $this->clientId($key) !== '' && $this->clientSecret($key) !== '' ? $provider : null;
    }

    /** @return string[] */
    private function listed(): array
    {
        $listed = ($this->config->values['auth'] ?? [])['providers'] ?? [];
        return is_array($listed) ? array_values(array_filter($listed, 'is_string')) : [];
    }

    /** @return array<string, IOAuthProvider> */
    private function providers(): array
    {
        if ($this->providers === null) {
            $this->providers = [];
            /** @var IOAuthProvider[] $registered */
            $registered = $this->container->serviceProvider->getAll(IOAuthProvider::class);
            foreach ($registered as $provider) {
                $this->providers[$provider->key()] = $provider;
            }
            $declared = ($this->config->values['auth'] ?? [])['oidc'] ?? [];
            if (is_array($declared)) {
                foreach ($declared as $key => $definition) {
                    if (is_string($key) && is_array($definition) && !isset($this->providers[$key])) {
                        $this->providers[$key] = new GenericOidcProvider($key, $definition);
                    }
                }
            }
        }
        return $this->providers;
    }

    /** Settings store first (rotatable); a config-declared issuer may carry its own. */
    private function clientId(string $key): string
    {
        return $this->credential($key, OAuthSettings::clientIdKey($key), 'clientId');
    }

    private function clientSecret(string $key): string
    {
        return $this->credential($key, OAuthSettings::clientSecretKey($key), 'clientSecret');
    }

    private function credential(string $key, string $settingKey, string $configKey): string
    {
        $value = trim($this->settings->getString($settingKey));
        if ($value === '') {
            $declared = (($this->config->values['auth'] ?? [])['oidc'] ?? [])[$key] ?? [];
            $value = is_array($declared) ? trim((string) ($declared[$configKey] ?? '')) : '';
        }
        return $value;
    }

    public function redirectUri(string $key): string
    {
        return rtrim((string) $this->config->values['baseUrl'], '/') . self::COOKIE_PATH . rawurlencode($key) . '/callback';
    }

    // --- step 1: send the visitor to the provider -----------------------------

    /**
     * The provider URL to redirect to, or null when the provider is not enabled.
     * Sets the flow cookie as a side effect.
     */
    public function start(string $key, ?string $returnTo): ?string
    {
        $provider = $this->enabled($key);
        if ($provider === null) {
            return null;
        }
        $state = UtilsService::randomHex(24);
        $nonce = UtilsService::randomHex(24);
        $this->writeFlow([
            'p' => $key,
            's' => $state,
            'n' => $nonce,
            'e' => time() + self::FLOW_LIFETIME,
            'r' => self::safeReturnPath($returnTo),
        ]);
        $query = array_merge($provider->authorizeParams(), [
            'response_type' => 'code',
            'client_id' => $this->clientId($key),
            'redirect_uri' => $this->redirectUri($key),
            'scope' => implode(' ', $provider->scopes()),
            'state' => $state,
            'nonce' => $nonce,
        ]);
        $url = $provider->authorizeUrl();
        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * A same-site absolute path, or null. "//evil.example", "/\evil.example" and anything with
     * a scheme would make the callback an open redirect.
     */
    public static function safeReturnPath(?string $path): ?string
    {
        if ($path === null || $path === '' || strlen($path) > 1024 || $path[0] !== '/') {
            return null;
        }
        if (str_starts_with($path, '//') || str_contains($path, '\\') || preg_match('/[\x00-\x1f]/', $path)) {
            return null;
        }
        return $path;
    }

    // --- step 2: the visitor comes back ---------------------------------------

    /**
     * Finish the flow: check it is the one this browser started, trade the code for the
     * provider's claims, then find or create the account and sign it in.
     */
    public function complete(string $key, ?string $code, ?string $state, ?string $providerError): OAuthResult
    {
        $flow = $this->readFlow();
        $this->clearFlow();
        $returnTo = is_array($flow) ? self::safeReturnPath($flow['r'] ?? null) : null;

        $provider = $this->enabled($key);
        if ($provider === null) {
            return OAuthResult::fail('failed', $returnTo);
        }
        // The cookie proves this browser started the flow; without the match, anyone could
        // hand a victim a callback link that signs them in to the attacker's account.
        if (
            $flow === null || ($flow['p'] ?? null) !== $key || !is_string($state) || $state === ''
            || !hash_equals((string) ($flow['s'] ?? ''), $state)
        ) {
            return $this->refuse($key, 'state', 'failed', $returnTo);
        }
        if ($providerError !== null && $providerError !== '') {
            // The person pressed Cancel at the provider, or it refused them.
            return OAuthResult::fail('cancelled', $returnTo);
        }
        if ($code === null || $code === '') {
            return $this->refuse($key, 'no-code', 'failed', $returnTo);
        }

        $claims = $this->exchange($provider, $code);
        if ($claims === null) {
            return $this->refuse($key, 'exchange', 'failed', $returnTo);
        }
        $problem = $this->claimsProblem($provider, $claims, (string) ($flow['n'] ?? ''));
        if ($problem !== null) {
            return $this->refuse($key, $problem, 'failed', $returnTo);
        }

        $result = $this->signIn($provider, $claims);
        $result->ReturnTo = $returnTo;
        return $result;
    }

    /**
     * Why these claims cannot be trusted, or null when they can.
     *
     * The signature is not checked, and does not need to be: the token came straight from the
     * provider's token endpoint over TLS in exchange for our client secret (OpenID Connect
     * Core 3.1.3.7, point 6). Everything else is checked.
     */
    public function claimsProblem(IOAuthProvider $provider, array $claims, string $nonce): ?string
    {
        if (!$provider->issuerValid($claims)) {
            return 'issuer';
        }
        $audience = $claims['aud'] ?? null;
        $clientId = $this->clientId($provider->key());
        if (!(is_array($audience) ? in_array($clientId, $audience, true) : $audience === $clientId)) {
            return 'audience';
        }
        if (!is_numeric($claims['exp'] ?? null) || (int) $claims['exp'] < time() - self::CLOCK_SKEW) {
            return 'expired';
        }
        if ($nonce === '' || !is_string($claims['nonce'] ?? null) || !hash_equals($nonce, $claims['nonce'])) {
            return 'nonce';
        }
        if (!is_string($claims['sub'] ?? null) || $claims['sub'] === '' || strlen($claims['sub']) > 255) {
            return 'subject';
        }
        return null;
    }

    /** The account behind verified claims: an existing identity, a link, or a new user. */
    private function signIn(IOAuthProvider $provider, array $claims): OAuthResult
    {
        $key = $provider->key();
        $subject = $claims['sub'];

        $identity = $this->findIdentity($key, $subject);
        if ($identity !== null) {
            /** @var UserEntity|null $user */
            $user = $this->users->getById($identity->UserId);
            if ($user === null) {
                return $this->refuse($key, 'orphan-identity', 'failed');
            }
            return $this->finish($user, false);
        }

        $email = strtolower(trim((string) ($claims['email'] ?? '')));
        if ($email === '' || strlen($email) > 255 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->refuse($key, 'no-email', 'no-email');
        }
        $verified = $provider->emailVerified($claims);

        /** @var UserEntity|null $existing */
        $existing = $this->users->firstOrDefault([
            [
                [UserEntityMap::PROPERTY_UserName, $email],
                [UserEntityMap::PROPERTY_Email, $email],
            ]
        ]);
        if ($existing !== null) {
            if (!$verified) {
                // The provider does not vouch for the address, so this sign-in proves nothing
                // about who owns the existing account. They sign in the way they registered.
                return $this->refuse($key, 'unverified-link', 'account-exists');
            }
            if ($this->auth->isDeactivated($existing)) {
                return $this->refuse($key, 'deactivated', 'failed');
            }
            if (!$existing->EmailConfirmed) {
                // Nobody ever proved they own this address, so whoever registered it may not
                // be the person now signing in. Their password and sessions must not survive
                // the real owner's arrival.
                $existing->Password = null;
                $this->users->update($existing, [UserEntityMap::PROPERTY_Password]);
                $this->auth->revokeSessions($existing->Id);
                $this->auth->activateUser($existing->Id);
                $this->confirmedNotifier->notify($existing->Id);
            }
            if (!$this->link($existing->Id, $key, $subject)) {
                return $this->refuse($key, 'link-failed', 'failed');
            }
            return $this->finish($existing, false);
        }

        // A new account. The app's own signup rules still apply to the address.
        if ($this->formGuard->emailSuffixRefused($email)) {
            return $this->refuse($key, 'email-suffix', 'not-accepted');
        }
        $user = new UserEntity();
        $user->UserName = $email;
        $user->Email = $email;
        $user->Active = $verified;
        $user->EmailConfirmed = $verified;
        if (is_string($claims['given_name'] ?? null)) {
            $user->FirstName = mb_substr($claims['given_name'], 0, 255);
        }
        if (is_string($claims['family_name'] ?? null)) {
            $user->LastName = mb_substr($claims['family_name'], 0, 255);
        }
        $registered = $this->auth->registerUser($user);
        if (!$registered->Success) {
            return $this->refuse($key, 'register-failed', 'failed');
        }
        if (!$this->link($user->Id, $key, $subject)) {
            return $this->refuse($key, 'link-failed', 'failed');
        }
        if ($verified) {
            $this->confirmedNotifier->notify($user->Id);
        } else {
            $verificationCode = $this->auth->createVerificationCode($user->Id);
            $this->emailService->dispatchUserActivateEmail($this->mapper->map(UserViewModel::class, $user), $verificationCode->Code);
        }
        return $this->finish($user, true);
    }

    private function finish(UserEntity $user, bool $created): OAuthResult
    {
        if ($this->auth->isDeactivated($user)) {
            return $this->refuse('-', 'deactivated', 'failed');
        }
        $this->auth->authorizeUser($user, true);
        $result = new OAuthResult();
        $result->Success = true;
        $result->Created = $created;
        return $result;
    }

    private function findIdentity(string $key, string $subject): ?UserIdentityEntity
    {
        return $this->identities->firstOrDefault([
            [UserIdentityEntityMap::PROPERTY_Provider, $key],
            [UserIdentityEntityMap::PROPERTY_Subject, $subject],
        ]);
    }

    private function link(int $userId, string $key, string $subject): bool
    {
        $identity = new UserIdentityEntity();
        $identity->UserId = $userId;
        $identity->Provider = $key;
        $identity->Subject = $subject;
        try {
            if ($this->identities->create($identity)) {
                return true;
            }
        } catch (\Throwable $e) {
            // A double click raced us to the unique index; the row below settles it.
        }
        return $this->findIdentity($key, $subject)?->UserId === $userId;
    }

    /** Journald line for a refusal. No address and no subject: the reason is enough to debug. */
    private function refuse(string $key, string $reason, string $error, ?string $returnTo = null): OAuthResult
    {
        echo "[OAuth] refused provider={$key} reason={$reason}" . PHP_EOL;
        return OAuthResult::fail($error, $returnTo);
    }

    // --- provider round trip --------------------------------------------------

    /** Trade the authorization code for the ID token's claims. Null on any failure. */
    private function exchange(IOAuthProvider $provider, string $code): ?array
    {
        $key = $provider->key();
        $parts = parse_url($provider->tokenUrl());
        if (!is_array($parts) || empty($parts['host'])) {
            return null;
        }
        $ssl = ($parts['scheme'] ?? 'https') === 'https';
        $client = new Client($parts['host'], (int) ($parts['port'] ?? ($ssl ? 443 : 80)), $ssl);
        $client->set(['timeout' => 10]);
        $client->setHeaders([
            'Host' => $parts['host'],
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ]);
        $client->post(($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : ''), http_build_query([
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->redirectUri($key),
            'client_id' => $this->clientId($key),
            'client_secret' => $this->clientSecret($key),
        ]));
        $status = $client->statusCode;
        $raw = $client->body;
        $client->close();
        if ($status !== 200) {
            echo "[OAuth] token endpoint provider={$key} status={$status}" . PHP_EOL;
            return null;
        }
        $decoded = json_decode($raw ?: 'null', true);
        return is_array($decoded) && is_string($decoded['id_token'] ?? null) ? self::tokenClaims($decoded['id_token']) : null;
    }

    /** The payload of a JWT, unverified (see claimsProblem for why that is enough here). */
    public static function tokenClaims(string $jwt): ?array
    {
        $segments = explode('.', $jwt);
        if (count($segments) !== 3) {
            return null;
        }
        $json = base64_decode(strtr($segments[1], '-_', '+/'), true);
        $claims = $json === false ? null : json_decode($json, true);
        return is_array($claims) ? $claims : null;
    }

    // --- the flow cookie ------------------------------------------------------

    private function writeFlow(array $flow): void
    {
        $payload = rtrim(strtr(base64_encode(json_encode($flow)), '+/', '-_'), '=');
        // SameSite=Lax: the provider sends the visitor back with a top-level GET, which Lax allows.
        $this->httpContext->response->setCookie(self::COOKIE_NAME, $payload . '.' . $this->sign($payload), time() + self::FLOW_LIFETIME, self::COOKIE_PATH, '', 1, 1, 'Lax');
    }

    private function readFlow(): ?array
    {
        $cookie = $this->httpContext->request->getCookie(self::COOKIE_NAME);
        if (!is_string($cookie) || substr_count($cookie, '.') !== 1) {
            return null;
        }
        [$payload, $signature] = explode('.', $cookie);
        if (!hash_equals($this->sign($payload), $signature)) {
            return null;
        }
        $flow = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);
        if (!is_array($flow) || (int) ($flow['e'] ?? 0) < time()) {
            return null;
        }
        return $flow;
    }

    private function clearFlow(): void
    {
        // Same path as it was set with, or the browser keeps it.
        $this->httpContext->response->setCookie(self::COOKIE_NAME, '', time() - 3600, self::COOKIE_PATH, '', 1, 1, 'Lax');
    }

    private function sign(string $payload): string
    {
        return hash_hmac('sha256', 'oauth-flow:' . $payload, (string) $this->config->values['hashSalt']);
    }
}
