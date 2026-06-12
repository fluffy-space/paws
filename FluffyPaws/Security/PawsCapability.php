<?php

namespace FluffyPaws\Security;

/**
 * Paws (framework layer) capability bits.
 *
 * Paws sits on top of Fluffy core (which owns only AccessAdmin) and below the
 * application. It introduces the bulk of the framework's capabilities: user and
 * role management plus the CMS/content admin areas.
 *
 * Paws owns capability bits 18..31 (core keeps 16..17; applications own 32..62,
 * see the app's Capability). Stay within this region to avoid collisions.
 *
 * Granted to the core Admin role via PawsPermissions::register().
 */
final class PawsCapability
{
    // --- Users & roles ---
    public const ManageUsers          = 1 << 18; // create/edit users
    public const ManageRoles          = 1 << 19; // change which roles/permissions a user has

    // --- CMS / content admin (per-domain "manage" grants) ---
    public const ManageBlog           = 1 << 20; // blog posts admin
    public const ManageMenu           = 1 << 21; // navigation menus admin
    public const ManageMedia          = 1 << 22; // media library admin
    public const ManagePages          = 1 << 23; // CMS pages admin
    public const ManageLocalization   = 1 << 24; // languages + locale resources admin
    public const ManageEmailTemplates = 1 << 25; // email templates admin
}
