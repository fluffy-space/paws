<?php

namespace FluffyPaws\Security;

use Fluffy\Security\PermissionRegistry;
use Fluffy\Security\Role;

/**
 * Registers the Paws layer's capabilities and grants them to the core roles.
 *
 * Called once at boot from PawsStartUp::configureServices, which runs after
 * Fluffy core's CorePermissions::register() and before the application's own
 * permission setup (e.g. Application\Security\AppPermissions). Idempotent.
 *
 * SuperAdmin reaches these automatically via the god short-circuit in
 * Permissions::effective(); here we grant them to the staff Admin role.
 */
final class PawsPermissions
{
    public static function register(): void
    {
        // Grant all Paws capabilities (user/role management + CMS admin) to the core Admin role.
        PermissionRegistry::extend(
            Role::Admin,
            PawsCapability::ManageUsers | PawsCapability::ManageRoles
                | PawsCapability::ManageBlog | PawsCapability::ManageMenu | PawsCapability::ManageMedia
                | PawsCapability::ManagePages | PawsCapability::ManageLocalization
                | PawsCapability::ManageEmailTemplates
        );

        // Names for the Paws capability bits, so they can be shipped to the client.
        PermissionRegistry::defineCapability(PawsCapability::ManageUsers, 'ManageUsers');
        PermissionRegistry::defineCapability(PawsCapability::ManageRoles, 'ManageRoles');
        PermissionRegistry::defineCapability(PawsCapability::ManageBlog, 'ManageBlog');
        PermissionRegistry::defineCapability(PawsCapability::ManageMenu, 'ManageMenu');
        PermissionRegistry::defineCapability(PawsCapability::ManageMedia, 'ManageMedia');
        PermissionRegistry::defineCapability(PawsCapability::ManagePages, 'ManagePages');
        PermissionRegistry::defineCapability(PawsCapability::ManageLocalization, 'ManageLocalization');
        PermissionRegistry::defineCapability(PawsCapability::ManageEmailTemplates, 'ManageEmailTemplates');
    }
}
