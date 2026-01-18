<?php

namespace App\Policies;

use App\Models\Game;
use App\Models\User;

class GamePolicy
{
    /**
     * Determine whether the user can view the game.
     */
    public function view(User $user, Game $game): bool
    {
        if ($game->trashed()) {
            return false;
        }

        if ($game->getGameType() === 'human') {
            return $game->isParticipant($user);
        }

        return $game->user_id === $user->id;
    }

    /**
     * Determine whether the user can update the game.
     */
    public function update(User $user, Game $game): bool
    {
        if ($game->trashed()) {
            return false;
        }

        if ($game->getGameType() !== 'bot') {
            return false;
        }

        return $game->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the game.
     */
    public function delete(User $user, Game $game): bool
    {
        if ($game->trashed()) {
            return false;
        }

        if ($game->getGameType() !== 'bot') {
            return false;
        }

        return $game->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the game.
     */
    public function restore(User $user, Game $game): bool
    {
        return $game->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the game.
     */
    public function forceDelete(User $user, Game $game): bool
    {
        return $game->user_id === $user->id;
    }
}
