<?php

namespace SharedPaws\Models\Auth;

/** Confirms a two-factor change on the account page. Each endpoint reads the fields it needs. */
class TwoFactorConfirmModel
{
    public ?string $Password = null;
    public ?string $Code = null;
}
