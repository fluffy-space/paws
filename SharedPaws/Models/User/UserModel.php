<?php

namespace SharedPaws\Models\User;

use SharedPaws\Models\BaseModel;

class UserModel extends BaseModel
{
    // User
    public string $UserName = '';
    public ?string $FirstName = null;
    public ?string $LastName = null;
    public ?string $Email = null;
    public ?string $Phone = null;
    // Hide password from public, no mapping 
    // !!! public ?string $Password = null;
    public ?string $NewPassword = null;
    public ?string $ConfirmPassword = null;
    public bool $Active = false;
    public bool $EmailConfirmed = false;
    public bool $IsAdmin = false;
}
