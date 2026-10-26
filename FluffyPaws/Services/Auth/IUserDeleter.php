<?php

namespace FluffyPaws\Services\Auth;

/**
 * Lets an app own what deleting a user means (admin UserController::Delete).
 *
 * Without one, the admin delete is a plain row delete. An app whose tables reference the
 * user with ON DELETE RESTRICT (or that must clean up first) registers an implementation;
 * the first registered one replaces the row delete entirely.
 */
interface IUserDeleter
{
    /** @return bool whether the deletion was accepted (it may finish later) */
    public function deleteUser(int $userId): bool;
}
