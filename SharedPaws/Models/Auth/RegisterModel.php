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
    /** Honeypot: hidden from people, filled by bots (AuthFormGuard). */
    public ?string $Website = null;
    public ?string $FormToken = null;
}
