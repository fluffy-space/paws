<?php

namespace SharedPaws\Models\Auth;

class UserAuthSessionModel
{
    public bool $isAuthenticated = false;
    public ?UserViewModel $user = null;
}
