<?php

namespace SharedPaws\Models\Auth;

class LoginModel
{
    public ?string $Email = null;
    public ?string $Password = null;
    public bool $RememberMe = true;
}
