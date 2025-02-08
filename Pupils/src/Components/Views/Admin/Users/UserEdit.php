<?php

namespace Pupils\Components\Views\Admin\Users;

use Pupils\Components\Guards\AdminGuard;
use Pupils\Components\Views\Admin\EditPage\EditPage;
use SharedPaws\Models\BaseModel;
use SharedPaws\Models\User\UserModel;
use SharedPaws\Models\User\UserValidation;
use SharedPaws\Validation\IValidationRules;
use Viewi\Components\Attributes\Middleware;

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
