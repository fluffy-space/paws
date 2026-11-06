<?php

namespace Pupils\Components\Views\Account;

use SharedPaws\Models\Auth\TwoFactorConfirmModel;
use SharedPaws\Models\Auth\TwoFactorSetupModel;
use SharedPaws\Models\Auth\TwoFactorStatusModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\DOM\DomEvent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;

/**
 * Two-factor sign-in on the account page: turn it on (password, scan, first code), see the
 * recovery codes once, renew them, turn it off. Talks to /api/account/two-factor, which always
 * acts on the signed-in user. A card body; the page puts it in its own card.
 *
 * $stage: loading, off, password (confirm before setup), scan, codes (shown once), on,
 * renew (code to get new codes), disable (password + code to turn off).
 */
class TwoFactorSettings extends BaseComponent
{
    public string $stage = 'loading';
    public TwoFactorStatusModel $status;
    public TwoFactorSetupModel $setup;
    public TwoFactorConfirmModel $confirm;
    /** @var string[] */
    public array $codes = [];
    public string $error = '';
    public bool $busy = false;
    public bool $copied = false;

    public function __construct(private HttpClient $http) {}

    public function init()
    {
        $this->status = new TwoFactorStatusModel();
        $this->setup = new TwoFactorSetupModel();
        $this->confirm = new TwoFactorConfirmModel();
    }

    public function mounted()
    {
        $this->load();
    }

    public function load()
    {
        $this->http->get('/api/account/two-factor')->then(function ($status) {
            $this->status = $status;
            $this->stage = $status->Enabled ? 'on' : 'off';
        }, function ($error) {
            $this->stage = 'off';
            $this->error = 'Could not load your two-factor settings. Reload the page to try again.';
        });
    }

    /** "Turn on": confirm the password first, when the account has one. */
    public function startSetup()
    {
        $this->reset();
        if ($this->status->HasPassword) {
            $this->stage = 'password';
        } else {
            $this->requestSetup();
        }
    }

    public function submitPassword(DomEvent $event)
    {
        $event->preventDefault();
        $this->requestSetup();
    }

    public function requestSetup()
    {
        $this->busy = true;
        $this->error = '';
        $this->http->post('/api/account/two-factor/setup', $this->confirm)->then(function ($setup) {
            $this->busy = false;
            $this->setup = $setup;
            $this->confirm = new TwoFactorConfirmModel();
            $this->stage = 'scan';
        }, function (Response $response) {
            $this->failed($response, 'Could not start the setup.');
        });
    }

    public function submitFirstCode(DomEvent $event)
    {
        $event->preventDefault();
        $this->busy = true;
        $this->error = '';
        $this->http->post('/api/account/two-factor/enable', $this->confirm)->then(function ($result) {
            $this->busy = false;
            $this->showCodes($result->Codes);
            $this->status->Enabled = true;
        }, function (Response $response) {
            $this->failed($response, 'Could not turn on two-factor sign-in.');
        });
    }

    public function startRenew()
    {
        $this->reset();
        $this->stage = 'renew';
    }

    public function submitRenew(DomEvent $event)
    {
        $event->preventDefault();
        $this->busy = true;
        $this->error = '';
        $this->http->post('/api/account/two-factor/recovery-codes', $this->confirm)->then(function ($result) {
            $this->busy = false;
            $this->showCodes($result->Codes);
        }, function (Response $response) {
            $this->failed($response, 'Could not make new recovery codes.');
        });
    }

    public function startDisable()
    {
        $this->reset();
        $this->stage = 'disable';
    }

    public function submitDisable(DomEvent $event)
    {
        $event->preventDefault();
        $this->busy = true;
        $this->error = '';
        $this->http->post('/api/account/two-factor/disable', $this->confirm)->then(function ($result) {
            $this->busy = false;
            $this->reset();
            $this->stage = 'loading';
            $this->load();
        }, function (Response $response) {
            $this->failed($response, 'Could not turn off two-factor sign-in.');
        });
    }

    /** The codes are saved: back to the status, which now counts them. */
    public function doneWithCodes()
    {
        $this->codes = [];
        $this->stage = 'loading';
        $this->load();
    }

    public function cancel()
    {
        $this->reset();
        $this->stage = $this->status->Enabled ? 'on' : 'off';
    }

    public function copyCodes()
    {
        <<<'javascript'
        if (window.isSecureContext && navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText($this.codes.join('\n')).then(function () {
                $this.copied = true;
            });
        }
        javascript;
    }

    public function downloadCodes()
    {
        <<<'javascript'
        const text = 'Recovery codes for ' + window.location.host + '\n'
            + 'Each code signs you in once when you do not have your authenticator app.\n\n'
            + $this.codes.join('\n') + '\n';
        const blob = new Blob([text], { type: 'text/plain' });
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'recovery-codes.txt';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(a.href);
        javascript;
    }

    private function showCodes(array $codes)
    {
        $this->codes = $codes;
        $this->copied = false;
        $this->confirm = new TwoFactorConfirmModel();
        $this->stage = 'codes';
    }

    private function reset()
    {
        $this->confirm = new TwoFactorConfirmModel();
        $this->setup = new TwoFactorSetupModel();
        $this->error = '';
        $this->busy = false;
    }

    private function failed(Response $response, string $fallback)
    {
        $this->busy = false;
        $body = $response->body;
        if ($body !== null && isset($body['errors']) && $body['errors']) {
            $this->error = $body['errors'][0];
        } else if ($body !== null && isset($body['message']) && $body['message']) {
            $this->error = $body['message'];
        } else {
            $this->error = $fallback;
        }
    }
}
