<?php

namespace Pupils\Components\Views\Shared\ImpersonationBanner;

use Pupils\Components\Services\Auth\AuthService;
use Viewi\Components\BaseComponent;
use Viewi\Components\Http\HttpClient;
use SharedPaws\Models\Auth\UserViewModel;
use SharedPaws\Support\UserDisplay;

/**
 * Global bar shown across the whole app (rendered once in BaseLayout) while an
 * admin is impersonating a user ("viewing as"). Reads the impersonation flags
 * from the session (Me) and offers a one-click Exit that clears the overlay and
 * returns to admin. Renders nothing when not impersonating.
 */
class ImpersonationBanner extends BaseComponent
{
    public bool $impersonating = false;
    public string $targetName = '';
    public string $impersonatorName = '';
    public string $redirectUrl = '/admin/user';

    public function __construct(private AuthService $auth, private HttpClient $http)
    {
    }

    public function init()
    {
        $this->auth->getUserSession(function ($session) {
            if ($session !== null && $session->impersonating) {
                $this->impersonating = true;
                $this->impersonatorName = $session->impersonatorName ?? '';
                $this->targetName = $this->displayName($session->user);
            }
        });
    }

    /** '' when there is no user; UserDisplay::forUser() itself never returns an empty string. */
    public function displayName(?UserViewModel $user): string
    {
        return $user === null ? '' : UserDisplay::forUser($user);
    }

    public function exitImpersonation()
    {
        $this->http->post('/api/impersonation/exit')->then(function ($res) {
            $this->redirectUrl = $res->redirectUrl ?? '/admin/user';
            $this->go();
        }, function () {
            $this->go();
        });
    }

    /** Hard navigation back to admin so the cleared overlay takes full effect. */
    public function go()
    {
        <<<'javascript'
        window.location.href = $this.redirectUrl;
        javascript;
    }
}
