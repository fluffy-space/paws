<?php

namespace SharedPaws\Models\Auth;

/** The second sign-in step: a code from the authenticator app, or a recovery code. */
class TwoFactorCodeModel
{
    public ?string $Code = null;
}
