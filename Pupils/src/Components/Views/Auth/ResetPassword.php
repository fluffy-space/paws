<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Localization\HasLocalization;
use Pupils\Components\Services\Session\SessionState;
use SharedPaws\Models\Auth\ResetPasswordModel;
use SharedPaws\Models\Auth\ResetPasswordValidation;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\UI\Components\Forms\ActionForm;
use Viewi\UI\Components\Validation\ValidationMessage;

class ResetPassword extends BaseComponent
{
    use HasLocalization;
    public ResetPasswordModel $resetModel;
    public bool $changed = false;
    public bool $loading = false;
    public ?ValidationMessage $generalMessages = null;
    private ?ActionForm $form = null;
    public ?ResetPasswordValidation $validation = null;

    public function __construct(
        private HttpClient $http,
        private SessionState $session,
        private string $code
    ) {}

    public function init()
    {
        $this->resetModel = new ResetPasswordModel();
        $this->resetModel->Code = $this->code;
        $this->validation = new ResetPasswordValidation($this->resetModel, fn(string $key) => $this->localization->t($key));
    }

    public function handleSubmit(DomEvent $event)
    {
        $event->preventDefault();
        // validate
        if (!$this->form->validate()) {
            return;
        }

        $this->loading = true;
        $this->generalMessages->show = false;
        $this->http
            ->withInterceptor(SessionState::class)
            ->post('/api/authorization/reset-password-confirm', $this->resetModel)
            ->then(
                function ($response) {
                    $this->handleResponse(false, $response);
                    <<<'javascript'
                    console.log(response);
                    javascript;
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
                $this->generalMessages->messages = [$this->localization->t('reset-password.something-went-wrong')];
            }
            $this->generalMessages->show = true;
        } elseif ($response['success']) {
            $this->changed = true;
        }
    }
}
