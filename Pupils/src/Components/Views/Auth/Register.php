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
            $redirectTo = $this->browserSession->getItem('redirectTo');
            if ($redirectTo !== null) {
                $this->browserSession->removeItem('redirectTo');
                $this->route->navigate($redirectTo);
            } else {
                $this->route->navigate('/');
            }
        }
    }
}
