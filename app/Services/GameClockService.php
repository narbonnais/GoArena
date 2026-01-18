<?php

namespace App\Services;

use App\Models\MultiplayerGame;

class GameClockService
{
    /**
     * Update the clock after a move is played.
     * Deducts elapsed time from the current player and adds increment.
     */
    public function updateClock(MultiplayerGame $game): array
    {
        $now = now();
        $lastMoveAt = $game->last_move_at ?? $game->created_at;
        $elapsedMs = (int) $lastMoveAt->diffInMilliseconds($now);

        // Get current player's time
        $currentPlayer = $game->current_player;
        $timeField = $currentPlayer === 'black' ? 'black_time_remaining_ms' : 'white_time_remaining_ms';
        $currentTime = $game->{$timeField};

        // Deduct elapsed time
        $newTime = max(0, $currentTime - $elapsedMs);

        // Add increment if time control has one
        $increment = $game->timeControl->increment_seconds * 1000;
        $newTime += $increment;

        return [
            'player' => $currentPlayer,
            'time_field' => $timeField,
            'previous_time' => $currentTime,
            'elapsed' => $elapsedMs,
            'increment' => $increment,
            'new_time' => $newTime,
        ];
    }

    /**
     * Check if a player has timed out.
     */
    public function checkTimeout(MultiplayerGame $game): ?string
    {
        if (! $game->isInProgress() || $game->score_phase === MultiplayerGame::SCORE_PHASE_MARKING) {
            return null;
        }

        $now = now();
        $lastMoveAt = $game->last_move_at ?? $game->created_at;
        $elapsedMs = (int) $lastMoveAt->diffInMilliseconds($now);

        // Get current player's time
        $currentPlayer = $game->current_player;
        $timeField = $currentPlayer === 'black' ? 'black_time_remaining_ms' : 'white_time_remaining_ms';
        $currentTime = $game->{$timeField};

        // Check if time ran out
        if ($currentTime - $elapsedMs <= 0) {
            return $currentPlayer;
        }

        return null;
    }

    /**
     * Get the current time remaining for both players.
     * Takes into account elapsed time since last move.
     */
    public function getCurrentTimes(MultiplayerGame $game): array
    {
        $now = now();
        $lastMoveAt = $game->last_move_at ?? $game->created_at;
        $elapsedMs = (int) $lastMoveAt->diffInMilliseconds($now);

        $blackTime = $game->black_time_remaining_ms;
        $whiteTime = $game->white_time_remaining_ms;

        // Deduct elapsed time from current player
        if ($game->isInProgress() && $game->score_phase !== MultiplayerGame::SCORE_PHASE_MARKING) {
            if ($game->current_player === 'black') {
                $blackTime = max(0, $blackTime - $elapsedMs);
            } else {
                $whiteTime = max(0, $whiteTime - $elapsedMs);
            }
        }

        return [
            'black_time_remaining_ms' => $blackTime,
            'white_time_remaining_ms' => $whiteTime,
            'current_player' => $game->current_player,
            'server_time' => $now->toIso8601String(),
        ];
    }

    /**
     * Format time in milliseconds to a human-readable string.
     */
    public function formatTime(int $ms): string
    {
        $totalSeconds = (int) floor($ms / 1000);
        $minutes = (int) floor($totalSeconds / 60);
        $seconds = $totalSeconds % 60;

        if ($minutes >= 60) {
            $hours = (int) floor($minutes / 60);
            $minutes = $minutes % 60;

            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
