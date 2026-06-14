<?php

namespace Pupils\Components\Views\Admin\AccessDenied;

use Pupils\Components\Guards\AdminGuard;
use Viewi\Components\BaseComponent;
use Viewi\Components\Attributes\Middleware;

/**
 * Shown when a signed-in admin reaches a page they can access the admin area
 * for, but lack the specific capability required. Guarded by AdminGuard only
 * (not HasCapability) so it never bounces the user back to /login.
 *
 * HasCapability::run() navigates here for the "admin, missing capability" case.
 */
#[Middleware([AdminGuard::class])]
class AccessDenied extends BaseComponent
{
    public string $title = 'Insufficient permissions';
}
