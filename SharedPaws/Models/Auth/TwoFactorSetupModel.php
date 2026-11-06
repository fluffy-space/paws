<?php

namespace SharedPaws\Models\Auth;

/** What the authenticator app needs: a QR code to scan, or the key to type in. */
class TwoFactorSetupModel
{
    /** The secret in groups of four, for typing in by hand. */
    public string $Secret = '';
    /** data:image/svg+xml URI of the QR code; empty when the app has no QR encoder. */
    public string $QrDataUri = '';
}
