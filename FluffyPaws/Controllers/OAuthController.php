<?php

namespace FluffyPaws\Controllers;

use Fluffy\Controllers\BaseController;
use Fluffy\Domain\Message\HttpContext;
use Fluffy\Domain\Message\ResponseBuilder;
use Fluffy\Swoole\RateLimit\IRateLimitService;
use FluffyPaws\Services\Auth\OAuth\OAuthService;

/**
 * Provider sign-in (Google, Microsoft, any OIDC issuer). Both actions are plain top-level GETs
 * that answer with a redirect, never JSON: the browser is navigating, not calling an API.
 *
 * With no provider enabled in config (`auth.providers`), both answer 404, which is what an app
 * that never asked for this gets.
 */
class OAuthController extends BaseController
{
    /** Where a failed or cancelled sign-in lands; the page reads `?oauth=<code>`. */
    private const FAILURE_PATH = '/login';
    /** Where a successful one lands (Pupils OAuthCompletePage). */
    private const COMPLETE_PATH = '/oauth/complete';

    function __construct(protected OAuthService $oauth) {}

    /**
     * GET /api/authorization/oauth/providers - the keys a visitor can use right now.
     *
     * What the buttons are drawn from, so a provider that is listed but has no credentials
     * never gets a button that leads nowhere. Empty for an app that enabled nothing.
     */
    public function Providers()
    {
        return ['providers' => $this->oauth->enabledKeys()];
    }

    /** GET /api/authorization/oauth/{provider}/start[?returnTo=/path] */
    public function Start(string $provider, IRateLimitService $rateLimit, HttpContext $httpContext, ?string $returnTo = null)
    {
        if ($this->oauth->enabled($provider) === null) {
            return $this->NotFound();
        }
        if (!$rateLimit->limit('oauth:ip:' . $httpContext->request->getIp(), 30, 5 * 60)) {
            return $this->leave(self::FAILURE_PATH . '?oauth=failed');
        }
        $url = $this->oauth->start($provider, $returnTo);
        return $url === null ? $this->NotFound() : $this->leave($url);
    }

    /** GET /api/authorization/oauth/{provider}/callback?code=&state= (the provider's redirect_uri) */
    public function Callback(string $provider, IRateLimitService $rateLimit, HttpContext $httpContext, ?string $code = null, ?string $state = null, ?string $error = null)
    {
        if ($this->oauth->enabled($provider) === null) {
            return $this->NotFound();
        }
        if (!$rateLimit->limit('oauth:ip:' . $httpContext->request->getIp(), 30, 5 * 60)) {
            return $this->leave(self::FAILURE_PATH . '?oauth=failed');
        }
        $result = $this->oauth->complete($provider, $code, $state, $error);
        if (!$result->Success) {
            $to = self::FAILURE_PATH . '?oauth=' . rawurlencode((string) $result->Error);
            if ($result->ReturnTo !== null) {
                // Keep the errand: the login page already carries ?redirect= through a sign-in.
                $to .= '&redirect=' . rawurlencode($result->ReturnTo);
            }
            return $this->leave($to);
        }
        // A page, not the destination itself: it runs what a form sign-in runs in the browser
        // (the signup event, an errand kept in browser storage) and then moves on.
        $query = [];
        if ($result->Created) {
            $query['new'] = '1';
        }
        if ($result->ReturnTo !== null) {
            $query['redirect'] = $result->ReturnTo;
        }
        return $this->leave(self::COMPLETE_PATH . (count($query) > 0 ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : ''));
    }

    /**
     * A redirect nothing may cache, carrying no referrer: the callback URL holds a one-time
     * code, and the provider has no need to learn which page the visitor clicked from.
     */
    private function leave(string $location)
    {
        return (new ResponseBuilder())
            ->WithHeaders([
                'Location' => $location,
                'Cache-Control' => 'no-store',
                'Referrer-Policy' => 'no-referrer',
            ])
            ->WithCode(302);
    }
}
