<?php

namespace SharedPaws\Models\Auth;

class RegisterModel
{
    public ?string $FirstName = null;
    public ?string $LastName = null;
    public ?string $Email = null;
    public ?string $Phone = null;
    public ?string $Password = null;
    public ?string $PasswordConfirmation = null;
}
