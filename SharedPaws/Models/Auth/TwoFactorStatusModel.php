<?php

namespace SharedPaws\Models\Auth;

/** Two-factor sign-in on the account page. */
class TwoFactorStatusModel
{
    public bool $Enabled = false;
    public int $RecoveryCodesLeft = 0;
    /** An account created through a provider has no password, so changes are confirmed by code alone. */
    public bool $HasPassword = true;
    /** The account can open the admin area, so two-factor sign-in must stay on. */
    public bool $Required = false;
}
