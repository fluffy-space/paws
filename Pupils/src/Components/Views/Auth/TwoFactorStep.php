<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Session\SessionState;
use SharedPaws\Models\Auth\TwoFactorCodeModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;

/**
 * The second sign-in step: a code from the authenticator app, or a recovery code. Shown by any
 * sign-in form whose answer was `twoFactor: true`; emits `done` once the server has signed the
 * user in, and the form carries on as after a one-step sign-in.
 */
class TwoFactorStep extends BaseComponent
{
    public TwoFactorCodeModel $model;
    public bool $loading = false;
    public string $error = '';
    /** Typing a recovery code instead of the app's code: other wording, a text keyboard. */
    public bool $useRecovery = false;

    public function __construct(private HttpClient $http) {}

    public function init()
    {
        $this->model = new TwoFactorCodeModel();
    }

    public function submit(DomEvent $event)
    {
        $event->preventDefault();
        if ($this->model->Code === null || trim($this->model->Code) === '') {
            $this->error = $this->useRecovery ? 'Enter a recovery code.' : 'Enter the 6-digit code.';
            return;
        }
        $this->loading = true;
        $this->error = '';
        $this->http
            ->withInterceptor(SessionState::class)
            ->post('/api/authorization/two-factor', $this->model)
            ->then(
                function ($response) {
                    $this->loading = false;
                    $this->emitEvent('done', true);
                },
                function (Response $response) {
                    $this->loading = false;
                    $body = $response->body;
                    if ($body !== null && isset($body['message']) && $body['message']) {
                        $this->error = $body['message'];
                    } else {
                        $this->error = 'Could not check the code. Please try again.';
                    }
                }
            );
    }

    public function toggleRecovery()
    {
        $this->useRecovery = !$this->useRecovery;
        $this->model = new TwoFactorCodeModel();
        $this->error = '';
    }
}
