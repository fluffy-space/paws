<?php

namespace Pupils\Components\Views\Admin\Users;

use Pupils\Components\Services\Auth\AuthService;
use SharedPaws\Models\Auth\TwoFactorStatusModel;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Http\Message\Response;
use Viewi\UI\Components\Alerts\AlertService;
use Viewi\UI\Components\Modals\ModalService;

/**
 * The user edit page "Two-factor" tab: whether the user has two-factor sign-in on, and, for a
 * SuperAdmin, a reset for someone who lost their phone and recovery codes.
 */
class UserTwoFactorAdmin extends BaseComponent
{
    /** The user id (passed from UserEdit). */
    public int $user = 0;
    public ?TwoFactorStatusModel $status = null;
    public bool $loaded = false;
    public bool $canReset = false;
    public bool $resetting = false;

    public function __construct(
        private HttpClient $http,
        private AuthService $auth,
        private ModalService $modal,
        private AlertService $messages
    ) {}

    public function mounted()
    {
        $this->load();
        $this->auth->getUserSession(function ($session) {
            $this->canReset = $session !== null && in_array('SuperAdmin', $session->roles);
        });
    }

    public function load()
    {
        $this->http->get("/api/admin/user/{$this->user}/two-factor")->then(function ($status) {
            $this->status = $status;
            $this->loaded = true;
        }, function ($error) {
            $this->loaded = true;
        });
    }

    public function reset()
    {
        $this->modal->confirm(
            'Turn off two-factor sign-in for this user? Do this only when you are sure it is them, e.g. they lost their phone and recovery codes. They are signed out everywhere and get an email.',
            function () {
                $this->resetting = true;
                $this->http->post("/api/admin/user/{$this->user}/two-factor/reset")->then(function () {
                    $this->resetting = false;
                    $this->messages->success('Two-factor sign-in is off for this user.', 5000);
                    $this->load();
                }, function (Response $response) {
                    $this->resetting = false;
                    $text = 'Could not reset two-factor sign-in.';
                    if ($response->body !== null && isset($response->body['errors']) && $response->body['errors']) {
                        $text = $response->body['errors'][0];
                    }
                    $this->messages->error($text, 6000);
                });
            }
        );
    }
}
