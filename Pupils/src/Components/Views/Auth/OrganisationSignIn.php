<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Session\SessionState;
use Viewi\Components\BaseComponent;
use Viewi\Components\Browser\BrowserSession;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\Components\Routing\ClientRoute;

/**
 * "Sign in with SSO" on the login page: sign-in through the visitor's organisation's own provider.
 *
 * The app owns SSO; this asks it. `GET /api/authorization/sso/hint` answers {enabled, resume}; an app
 * that does not serve it (404) gets nothing drawn. The work email goes to
 * `POST /api/authorization/sso/start` {Email, ReturnTo}, which answers {url} for the provider or
 * {error: 'not-set-up'}; the browser then leaves with a full navigation (the provider is another
 * site). The address is never put in a URL.
 *
 * resume: this browser's organisation session has ended (the app keeps its own hint). Reached
 * through a guard's bounce (`?redirect=`), the page goes round the provider again by itself, once
 * per browser session, so a provider that keeps failing cannot loop. A deliberate visit, or one
 * with an error on the URL, never does.
 *
 * Client-only (rendered): the hint and browser session exist only in the browser.
 */
class OrganisationSignIn extends BaseComponent
{
    /** Same-site path to come back to ('' = the default landing). */
    public string $redirect = '';

    public bool $enabled = false;
    public bool $open = false;
    public bool $loading = false;
    public string $email = '';
    public string $message = '';
    public string $target = '';
    private bool $started = false;

    public function __construct(private HttpClient $http, private ClientRoute $route, private BrowserSession $browserSession) {}

    public function init()
    {
        $params = $this->route->getQueryParams();
        $code = $params === null ? '' : (string) ($params['sso'] ?? '');
        $oauth = $params === null ? '' : (string) ($params['oauth'] ?? '');
        $this->message = $this->messageFor($code, $oauth);
        if ($this->message !== '') {
            $this->open = true;
        }
    }

    public function rendered()
    {
        if ($this->started) {
            return;
        }
        $this->started = true;
        $this->http->get('/api/authorization/sso/hint')->then(function ($response) {
            $this->enabled = $response['enabled'] === true;
            if ($this->enabled && $response['resume'] === true) {
                $this->resume();
            }
        }, function () {
            // The app offers no SSO: nothing is drawn.
        });
    }

    /** Round the provider again after the organisation session ended, once per browser session. */
    public function resume()
    {
        $params = $this->route->getQueryParams();
        $bounced = $params !== null && isset($params['redirect']);
        $errored = $this->message !== '';
        if (!$bounced || $errored || $this->browserSession->getItem('ssoResumeTried') !== null) {
            $this->open = true;
            return;
        }
        $this->browserSession->setItem('ssoResumeTried', '1');
        $this->target = '/api/authorization/sso/resume' . ($this->redirect !== '' ? '?returnTo=' . rawurlencode($this->redirect) : '');
        $this->leave();
    }

    public function toggle()
    {
        $this->open = true;
    }

    public function submit(DomEvent $event)
    {
        $event->preventDefault();
        if (trim($this->email) === '') {
            $this->message = 'Enter your work email.';
            return;
        }
        $this->loading = true;
        $this->message = '';
        $this->http
            ->withInterceptor(SessionState::class)
            ->post('/api/authorization/sso/start', ['Email' => trim($this->email), 'ReturnTo' => $this->redirect])
            ->then(function ($response) {
                if (isset($response['url']) && $response['url'] !== '') {
                    $this->target = $response['url'];
                    $this->leave();
                    return;
                }
                $this->loading = false;
                $this->message = $this->messageFor('not-set-up', '');
            }, function (Response $response) {
                $this->loading = false;
                $body = $response->body;
                $this->message = $body !== null && isset($body['message']) ? $body['message'] : 'Something went wrong. Please try again.';
            });
    }

    /** What a code on the URL means (`?sso=` from the SSO callback, `?oauth=sso-required` from a provider sign-in). */
    public function messageFor(string $code, string $oauth): string
    {
        if ($oauth === 'sso-required' || $code === 'required') {
            return 'Your organisation signs you in here. Enter your work email to continue.';
        }
        if ($code === 'ended') {
            return 'Your session with your organisation ended. Enter your work email to sign in again.';
        }
        if ($code === 'not-set-up') {
            return 'Sign-in with your organisation is not set up for this address. Sign in with your password, or ask your administrator.';
        }
        if ($code === 'cancelled') {
            return 'The sign-in with your organisation was cancelled.';
        }
        if ($code === 'domain') {
            return 'Your organisation\'s sign-in does not cover this address.';
        }
        if ($code === 'deactivated') {
            return 'This account has been disabled.';
        }
        if ($code === 'seats') {
            return 'Your organisation\'s workspace has no free seats. Ask an administrator to make room, then try again.';
        }
        if ($code === 'failed') {
            return 'The sign-in with your organisation did not go through. Please try again.';
        }
        return '';
    }

    /** A full navigation: the provider is another site, and the app must start again signed in. */
    public function leave()
    {
        <<<'javascript'
        window.location.assign($this.target);
        javascript;
    }
}
