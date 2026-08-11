<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Localization\HasLocalization;
use Pupils\Components\Services\Session\SessionState;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;

/**
 * "Send it again" for the account-activation email — the one escape hatch out of an
 * unconfirmed account (the code expires after 3 days and the mail can land in spam).
 *
 * A child component rather than page code because the same control belongs on the
 * post-signup welcome page, on the in-app "confirm your email" notice, and on the
 * failed-confirmation page. It renders inline (a link-styled button by default), so it
 * can sit inside a sentence; pass $linkClass to make it a real button somewhere else.
 *
 * The endpoint resends only to the signed-in user's own address, so this needs no props.
 */
class ResendVerification extends BaseComponent
{
    use HasLocalization;

    public string $linkClass = 'btn btn-link p-0 align-baseline';
    public bool $sending = false;
    public bool $sent = false;
    public string $error = '';

    public function __construct(private HttpClient $http) {}

    public function resend()
    {
        if ($this->sending || $this->sent) {
            return;
        }
        $this->sending = true;
        $this->error = '';
        $this->http
            ->withInterceptor(SessionState::class)
            ->post('/api/authorization/resend-verification')
            ->then(
                function ($response) {
                    $this->sending = false;
                    $this->sent = true;
                },
                function (Response $response) {
                    $this->sending = false;
                    // 429 (three per 15 minutes) and 401 both carry a usable message; anything
                    // else falls back, so the control never dead-ends on a bare failure.
                    if ($response->body !== null && $response->body['message']) {
                        $this->error = $response->body['message'];
                    } else {
                        $this->error = $this->localization->t('verify-email.resend-failed');
                    }
                }
            );
    }
}
