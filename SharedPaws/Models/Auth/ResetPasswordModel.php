<?php

namespace SharedPaws\Models\Auth;

class ResetPasswordModel
{
    public ?string $Code = null;
    public ?string $Password = null;
    public ?string $PasswordConfirmation = null;
}
