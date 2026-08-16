<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Localization\HasLocalization;
use Pupils\Components\Services\Session\SessionState;
use SharedPaws\Models\Auth\ConfirmEmailModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\Components\Routing\ClientRoute;

/**
 * /account/confirm/{code} — where the activation email lands.
 *
 * The whole point of this page is that it does nothing on load. Opening it confirms nothing;
 * a person has to press the button, which POSTs the code. Before this existed the emailed
 * link was a GET that activated the account, and mailbox security scanners fetch every link
 * in inbound mail on delivery — Microsoft Defender's Safe Links most visibly — so accounts
 * on those providers came out confirmed before anyone read the mail, with the single-use
 * code already spent. Scanners issue GETs; they do not press buttons.
 *
 * Both outcomes hand off to /account/verified[/failed], so the "what now" copy (and the
 * resend offer, and the already-confirmed detection) stays in one place.
 */
class ConfirmEmailPage extends BaseComponent
{
    use HasLocalization;

    public bool $confirming = false;
    public string $error = '';

    public function __construct(
        private string $code,
        private HttpClient $http,
        private ClientRoute $route
    ) {}

    public function confirm()
    {
        if ($this->confirming) {
            return;
        }
        $this->confirming = true;
        $this->error = '';
        $confirmModel = new ConfirmEmailModel();
        $confirmModel->Code = $this->code;
        $this->http
            ->withInterceptor(SessionState::class)
            ->post('/api/authorization/confirm-email', $confirmModel)
            ->then(
                function ($response) {
                    $this->route->navigate('/account/verified');
                },
                function (Response $response) {
                    $this->confirming = false;
                    // A refused code is an outcome, not a fault: expired, already used, or
                    // mistyped all come back 400 and the failure page explains them and
                    // offers a fresh email. Anything else (429, 5xx, offline) is worth
                    // reporting where the button is, so the click can just be repeated.
                    if ($response->status === 400) {
                        $this->route->navigate('/account/verified/failed');
                        return;
                    }
                    if ($response->body !== null && $response->body['message']) {
                        $this->error = $response->body['message'];
                    } else {
                        $this->error = $this->localization->t('confirm-email.error');
                    }
                }
            );
    }
}
