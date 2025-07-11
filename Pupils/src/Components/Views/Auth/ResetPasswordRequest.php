<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Localization\HasLocalization;
use Pupils\Components\Services\Session\SessionState;
use SharedPaws\Validation\ValidationRules;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\UI\Components\Forms\ActionForm;
use Viewi\UI\Components\Validation\ValidationMessage;

class ResetPasswordRequest extends BaseComponent
{
    use HasLocalization;
    public string $email = '';
    public bool $loading = false;
    public ?ValidationMessage $generalMessages = null;
    public ?ActionForm $form = null;
    public bool $emailSent = false;

    public function __construct(private HttpClient $http, private SessionState $session)
    {
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
            ->post('/api/authorization/reset-password', ['Email' => $this->email])
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
            $this->emailSent = true;
        }
    }

    function getValidationRules(): array
    {
        return ValidationRules::rules($this)
            ->required('email', $this->localization->t('register.validation.email-required'))
            ->email('email', $this->localization->t('register.validation.wrong-email'))
            ->toList();
    }
}
