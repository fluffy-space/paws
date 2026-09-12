<?php

namespace Pupils\Components\Views\Auth;

use SharedPaws\Models\Auth\RegisterModel;
use SharedPaws\Models\Auth\RegisterValidation;
use Pupils\Components\Services\Analytics\AnalyticsService;
use Pupils\Components\Services\Auth\AuthService;
use Viewi\Components\Browser\BrowserSession;
use Pupils\Components\Services\Localization\HasLocalization;
use Pupils\Components\Services\Session\SessionState;
use Viewi\UI\Components\Forms\ActionForm;
use Viewi\UI\Components\Validation\ValidationMessage;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\Components\Routing\ClientRoute;

class Register extends BaseComponent
{
    use HasLocalization;
    public RegisterModel $registerModel;
    public bool $loading = false;
    public ?ValidationMessage $generalMessages = null;
    private ?ActionForm $registerForm = null;
    public ?RegisterValidation $validation = null;
    public bool $isPayment = false;
    public bool $trackedView = false;

    public function __construct(private HttpClient $http, private ClientRoute $route, private AuthService $auth, private BrowserSession $browserSession, private AnalyticsService $analytics) {}

    public function init()
    {
        $this->registerModel = new RegisterModel();
        $this->validation = new RegisterValidation($this->registerModel, fn(string $key) => $this->localization->t($key));
        $productId = $this->browserSession->getItem('purchaseItem');
        if ($productId !== null) {
            $productId = (int)$productId;
            $this->isPayment = true;
        }
    }

    /**
     * Client-only hook: `init` also runs during SSR, where the analytics call is a no-op, and
     * hydration does not re-run it — an event fired from `init` never reaches the browser at all.
     */
    public function rendered()
    {
        if (!$this->trackedView) {
            $this->trackedView = true;
            $this->analytics->track('signup_started');
        }
    }

    public function handleSubmit(DomEvent $event)
    {
        $event->preventDefault();
        // validate
        if (!$this->registerForm->validate()) {
            return;
        }

        $this->loading = true;
        $this->generalMessages->show = false;
        $this->http
            ->withInterceptor(SessionState::class)
            ->post('/api/authorization/register', $this->registerModel)
            ->then(
                function ($response) {
                    $this->handleResponse(false, $response);
                },
                function (Response $response) {
                    $this->handleResponse(true, $response->body);
                }
            );
    }

    public function handleResponse(bool $hasError, $response = null)
    {
        $this->loading = false;
        if ($hasError) {
            if ($response['errors']) {
                $this->generalMessages->messages = $response['errors'];
            } else if ($response['message']) {
                $this->generalMessages->messages = [$response['message']];
            } else {
                $this->generalMessages->messages = [$this->localization->t('register.validation.failed')];
            }
            $this->generalMessages->show = true;
        } elseif ($response['success']) {
            // The conversion. No URL of its own — the next thing that happens is a navigate — so
            // page-view tracking cannot see it.
            $this->analytics->track('signup_completed');
            $this->auth->reset();
            $redirectTo = $this->browserSession->getItem('redirectTo') ?? $this->redirectFromQuery();
            if ($redirectTo !== null) {
                // An interrupted intent (buying a plan, claiming a link) wins: it is why they
                // signed up, and the "confirm your email" notice follows them into the app.
                $this->browserSession->removeItem('redirectTo');
                $this->route->navigate($redirectTo);
            } else {
                // Not the home page: registration just sent an activation email, and landing on
                // marketing copy says nothing about it. /welcome names the address and moves on.
                $this->route->navigate('/welcome');
            }
        }
    }

    /**
     * `?redirect=/some/path`, set by a guard that bounced an unauthenticated visitor here.
     *
     * A query parameter rather than browser session storage, because the guard also runs during
     * SSR — a link opened directly, such as "keep this link" from an anonymous result — where
     * there is no browser storage to write to.
     *
     * Only a same-site absolute path is accepted: "//evil.example" and "https://evil.example"
     * would make this an open redirect.
     */
    private function redirectFromQuery(): ?string
    {
        $params = $this->route->getQueryParams();
        $to = $params === null ? '' : (string) ($params['redirect'] ?? '');
        if ($to === '' || substr($to, 0, 1) !== '/' || substr($to, 0, 2) === '//') {
            return null;
        }
        return $to;
    }

    /** Keeps `?redirect=` on the "log in" link, so an existing user finishes the errand too. */
    public function loginUrl(): string
    {
        $to = $this->redirectFromQuery();
        return $to === null ? '/login' : '/login?redirect=' . rawurlencode($to);
    }
}
