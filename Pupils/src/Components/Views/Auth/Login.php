<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Auth\AuthService;
use Pupils\Components\Services\Localization\HasLocalization;
use Pupils\Components\Services\Session\SessionState;
use SharedPaws\Models\Auth\LoginModel;
use SharedPaws\Models\Auth\LoginValidation;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Browser\BrowserSession;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\Components\Routing\ClientRoute;
use Viewi\UI\Components\Forms\ActionForm;
use Viewi\UI\Components\Validation\ValidationMessage;

class Login extends BaseComponent
{
    use HasLocalization;
    public LoginModel $loginModel;
    public bool $loading = false;
    public ?ValidationMessage $generalMessages = null;
    private ?ActionForm $loginForm = null;
    public ?LoginValidation $validation = null;

    public function __construct(
        private HttpClient $http,
        private ClientRoute $route,
        private AuthService $auth,
        private BrowserSession $browserSession
    ) {}

    public function init()
    {
        $this->loginModel = new LoginModel();
        $this->validation = new LoginValidation($this->loginModel, $this->translateFn());
    }

    public function handleSubmit(DomEvent $event)
    {
        $event->preventDefault();
        // validate
        if (!$this->loginForm->validate()) {
            return;
        }

        $this->loading = true;
        $this->generalMessages->show = false;
        $this->http
            ->withInterceptor(SessionState::class)
            ->post('/api/authorization/login', $this->loginModel)
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
                $this->generalMessages->messages = [$this->localization->t('login.validation.wrong-username-or-password')];
            }
            $this->generalMessages->show = true;
        } elseif ($response['success']) {
            $this->auth->reset();

            $redirectTo = $this->browserSession->getItem('redirectTo') ?? $this->redirectFromQuery();
            if ($redirectTo !== null) {
                $this->browserSession->removeItem('redirectTo');
                $this->route->navigate($redirectTo);
            } else {
                $this->auth->getUserSession(function (?UserAuthSessionModel $userSession) {
                    if ($userSession !== null && $userSession->user?->CanAccessAdmin) {
                        $this->route->navigate('/admin');
                    } else {
                        $this->route->navigate('/');
                    }
                });
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

    /** Keeps `?redirect=` on the "create an account" link, so registering finishes the errand too. */
    public function registerUrl(): string
    {
        $to = $this->redirectFromQuery();
        return $to === null ? '/register' : '/register?redirect=' . rawurlencode($to);
    }
}
