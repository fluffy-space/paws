<?php

namespace Pupils\Components\Views\Admin\Users;

use Pupils\Components\Guards\HasCapability;
use Pupils\Components\Services\Auth\AuthService;
use Pupils\Components\Views\Admin\EditPage\EditPage;
use SharedPaws\Models\BaseModel;
use SharedPaws\Models\User\UserModel;
use SharedPaws\Models\User\UserValidation;
use SharedPaws\Validation\IValidationRules;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Routing\ClientRoute;
use Viewi\UI\Components\Alerts\AlertService;
use Viewi\UI\Components\Modals\ModalService;

/**
 *
 * @package Pupils\Components\Views\Admin\Users
 * @property UserModel $item
 */
#[Middleware([[HasCapability::class, 'ManageUsers']])]
class UserEdit extends EditPage
{
    public string $segment = 'user';
    public bool $changePassword = false;
    public string $name = "User";
    /** SuperAdmin-only "view as user" action, shown on an existing user. */
    public bool $canImpersonate = false;

    public function __construct(
        public int $id,
        private HttpClient $http,
        private AlertService $messages,
        private ClientRoute $route,
        private AuthService $auth,
        private ModalService $modal
    ) {
        parent::__construct($id, $http, $messages, $route);
    }

    public function init()
    {
        parent::init();
        // Edit mode gets the role catalog from GET /user/{id}; create mode needs it fetched.
        if ($this->id <= 0) {
            $this->http->get('/api/admin/user/roles')
                ->then(function ($roles) {
                    $this->item->Roles = $roles;
                });
        } else {
            // Impersonation is SuperAdmin-only; show the action only to a SuperAdmin.
            $this->auth->getUserSession(function ($session) {
                $this->canImpersonate = $session !== null && in_array('SuperAdmin', $session->roles);
            });
        }
    }

    public function impersonate()
    {
        $this->modal->confirm(
            "View the app as this user? You'll be signed in as them until you exit.",
            function () {
                $this->http->post("/api/admin/user/{$this->id}/impersonate")
                    ->then(function () {
                        $this->go();
                    }, function () {
                        $this->messages->error('Could not start impersonation.', 5000);
                    });
            }
        );
    }

    /** Hard navigation into the member app so the impersonation cookie takes full effect. */
    public function go()
    {
        <<<'javascript'
        window.location.href = '/app';
        javascript;
    }

    public function getValidation(BaseModel $item): ?IValidationRules
    {
        return new UserValidation($item);
    }

    public function getNewItem(): BaseModel
    {
        return new UserModel();
    }

    public function togglePasswordChange()
    {
        $this->changePassword = !$this->changePassword;
        if (!$this->changePassword) {
            $this->item->NewPassword = null;
            $this->item->ConfirmPassword = null;
        }
    }
}
