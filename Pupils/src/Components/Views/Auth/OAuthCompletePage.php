<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Analytics\AnalyticsService;
use Pupils\Components\Services\Auth\AuthService;
use Pupils\Components\Services\Localization\HasLocalization;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Browser\BrowserSession;
use Viewi\Components\Routing\ClientRoute;

/**
 * /oauth/complete - where a provider sign-in lands, for a moment, before moving on.
 *
 * The provider round trip is a chain of full-page redirects, so nothing in the browser ran when
 * the account was signed in. This page does what Login and Register do after a successful
 * submit: counts a new signup, picks up an interrupted errand (browser session `redirectTo`,
 * then `?redirect=`), and goes there.
 *
 * It leaves with a full navigation, not the client router: the target can be a server route
 * (an invitation link), and the app must start again with the signed-in session.
 */
class OAuthCompletePage extends BaseComponent
{
    use HasLocalization;

    public string $title = 'Signing in';
    public bool $started = false;
    public string $target = '/';

    public function __construct(private ClientRoute $route, private AuthService $auth, private BrowserSession $browserSession, private AnalyticsService $analytics) {}

    /** Client-only: there is no browser session or analytics during SSR. */
    public function rendered()
    {
        if ($this->started) {
            return;
        }
        $this->started = true;
        $this->auth->reset();
        $this->auth->getUserSession(function (?UserAuthSessionModel $userSession) {
            if ($userSession === null || !$userSession->isAuthenticated) {
                // Opened by hand, or the session did not stick: nothing to complete.
                $this->target = '/login';
                $this->leave();
                return;
            }
            $params = $this->route->getQueryParams();
            $created = $params !== null && ($params['new'] ?? '') === '1';
            $redirectTo = $this->browserSession->getItem('redirectTo');
            if ($redirectTo === null) {
                $redirectTo = $this->redirectFromQuery();
            }
            if ($redirectTo !== null) {
                $this->browserSession->removeItem('redirectTo');
                $this->target = $redirectTo;
            } elseif ($created) {
                $this->target = '/welcome';
            } elseif ($userSession->user !== null && $userSession->user->CanAccessAdmin) {
                $this->target = '/admin';
            } else {
                $this->target = '/';
            }
            if ($created) {
                // The conversion, same event the register form fires. This page is about to
                // unload, so it waits for the event to be sent before leaving.
                $this->analytics->trackAndWait('signup_completed', function () {
                    $this->leave();
                });
            } else {
                $this->leave();
            }
        });
    }

    /** Only a same-site absolute path: "//evil.example" would make this an open redirect. */
    private function redirectFromQuery(): ?string
    {
        $params = $this->route->getQueryParams();
        $to = $params === null ? '' : (string) ($params['redirect'] ?? '');
        if ($to === '' || substr($to, 0, 1) !== '/' || substr($to, 0, 2) === '//') {
            return null;
        }
        return $to;
    }

    public function leave()
    {
        <<<'javascript'
        window.location.replace($this.target);
        javascript;
    }
}
