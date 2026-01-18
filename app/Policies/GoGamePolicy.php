<?php

namespace App\Policies;

use App\Models\GoGame;
use App\Models\User;

class GoGamePolicy
{
    /**
     * Determine whether the user can view the game.
     * Denies access to soft-deleted games.
     */
    public function view(User $user, GoGame $goGame): bool
    {
        // Deny access to soft-deleted games
        if ($goGame->trashed()) {
            return false;
        }

        return $user->id === $goGame->user_id;
    }

    /**
     * Determine whether the user can update the game.
     * Denies access to soft-deleted games.
     */
    public function update(User $user, GoGame $goGame): bool
    {
        // Cannot update soft-deleted games
        if ($goGame->trashed()) {
            return false;
        }

        return $user->id === $goGame->user_id;
    }

    /**
     * Determine whether the user can delete the game.
     * Denies access to already soft-deleted games.
     */
    public function delete(User $user, GoGame $goGame): bool
    {
        // Cannot delete already soft-deleted games
        if ($goGame->trashed()) {
            return false;
        }

        return $user->id === $goGame->user_id;
    }

    /**
     * Determine whether the user can restore the game.
     */
    public function restore(User $user, GoGame $goGame): bool
    {
        return $user->id === $goGame->user_id;
    }

    /**
     * Determine whether the user can permanently delete the game.
     */
    public function forceDelete(User $user, GoGame $goGame): bool
    {
        return $user->id === $goGame->user_id;
    }
}
