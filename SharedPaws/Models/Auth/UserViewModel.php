<?php

namespace SharedPaws\Models\Auth;

class UserViewModel
{
    public int $Id;
    public string $UserName;
    public ?string $FirstName = null;
    public ?string $LastName = null;
    public ?string $Email = null;
    public ?string $Phone = null;
    public bool $Active = false;
    public bool $EmailConfirmed = false;
    public bool $IsAdmin = false;
}
