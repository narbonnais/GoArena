<?php

use App\Models\MultiplayerGame;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Matchmaking channel - private to each user
Broadcast::channel('matchmaking.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Game presence channel - only for game participants
Broadcast::channel('game.{gameId}', function ($user, $gameId) {
    $game = MultiplayerGame::find($gameId);

    if (! $game || ! $game->isParticipant($user)) {
        return false;
    }

    return [
        'id' => $user->id,
        'name' => $user->display_name_or_name,
        'color' => $game->getPlayerColor($user),
    ];
});

// Public game channel - for spectators (read-only)
Broadcast::channel('game.{gameId}.spectate', function ($user, $gameId) {
    $game = MultiplayerGame::find($gameId);

    if (! $game) {
        return false;
    }

    // Anyone can spectate
    return [
        'id' => $user->id,
        'name' => $user->display_name_or_name,
        'is_spectator' => true,
    ];
});
