<?php

namespace Pupils\Components\Views\Auth;

use Pupils\Components\Services\Auth\AuthService;
use Pupils\Components\Services\Localization\HasLocalization;
use SharedPaws\Models\Auth\UserAuthSessionModel;
use Viewi\Components\BaseComponent;

/**
 * /welcome — where registration lands. Register signs the user straight in and fires the
 * activation email, so without this page the confirmation step is invisible: the user is
 * dropped on the home page and never told to check their inbox.
 *
 * Deliberately not a gate. The account already works; the page states what was sent, to
 * which address, and offers a resend — then gets out of the way with a continue link.
 * Apps override this component to point $continueTo at their own first-run destination.
 */
class WelcomePage extends BaseComponent
{
    use HasLocalization;

    public string $title = 'Welcome';
    public string $email = '';
    public bool $confirmed = false;
    public string $continueTo = '/';

    public function __construct(private AuthService $auth) {}

    public function init()
    {
        $this->auth->getUserSession(function (UserAuthSessionModel $session) {
            if ($session->isAuthenticated && $session->user !== null) {
                // UserName is the login address; Email is the display copy and can be empty.
                $this->email = $session->user->Email ? $session->user->Email : $session->user->UserName;
                $this->confirmed = $session->user->EmailConfirmed;
            }
        });
    }
}
