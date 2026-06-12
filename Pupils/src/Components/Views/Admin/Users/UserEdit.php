<?php

namespace Pupils\Components\Views\Admin\Users;

use Pupils\Components\Guards\AdminGuard;
use Pupils\Components\Views\Admin\EditPage\EditPage;
use SharedPaws\Models\BaseModel;
use SharedPaws\Models\User\UserModel;
use SharedPaws\Models\User\UserValidation;
use SharedPaws\Validation\IValidationRules;
use Viewi\Components\Attributes\Middleware;
use Viewi\Components\Http\HttpClient;
use Viewi\Components\Routing\ClientRoute;
use Viewi\UI\Components\Alerts\AlertService;

/**
 * 
 * @package Pupils\Components\Views\Admin\Users
 * @property UserModel $item
 */
#[Middleware([AdminGuard::class])]
class UserEdit extends EditPage
{
    public string $segment = 'user';
    public bool $changePassword = false;
    public string $name = "User";

    public function __construct(
        public int $id,
        private HttpClient $http,
        private AlertService $messages,
        private ClientRoute $route
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
        }
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
