<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property int $user_id
 * @property int $board_size
 * @property string|null $ai_level @deprecated AI levels removed - field kept for backwards compatibility
 * @property float $komi
 * @property string|null $winner
 * @property string|null $end_reason
 * @property float|null $score_margin
 * @property int $move_count
 * @property float|null $black_score
 * @property float|null $white_score
 * @property int $black_captures
 * @property int $white_captures
 * @property array $move_history
 * @property int|null $duration_seconds
 * @property bool $is_finished
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read array $validated_move_history
 */
class GoGame extends Game
{
    protected static function booted(): void
    {
        static::addGlobalScope('bot', function (Builder $query) {
            $query->where('game_type', 'bot');
        });

        static::creating(function (self $game) {
            $game->game_type = 'bot';
            $game->is_ranked = $game->is_ranked ?? false;
            $game->ai_level = $game->ai_level ?: 'bot';
            $game->black_player_id = $game->black_player_id ?? $game->user_id;
            $game->status = $game->status ?? ($game->is_finished ? 'finished' : 'active');
        });

        static::saving(function (self $game) {
            $game->game_type = 'bot';
            $game->ai_level = $game->ai_level ?: 'bot';
            $game->black_player_id = $game->black_player_id ?? $game->user_id;
            if ($game->is_finished !== null) {
                $game->status = $game->is_finished ? 'finished' : 'active';
            }
        });
    }

    /**
     * Scope a query to only include finished games.
     */
    public function scopeFinished(Builder $query): Builder
    {
        return $query->where('status', 'finished');
    }

    /**
     * Scope a query to only include unfinished games.
     */
    public function scopeUnfinished(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope a query to only include games won by user (black).
     */
    public function scopeWon(Builder $query): Builder
    {
        return $query->finished()->where('winner', 'black');
    }

    /**
     * Scope a query to only include games lost by user (white wins).
     */
    public function scopeLost(Builder $query): Builder
    {
        return $query->finished()->where('winner', 'white');
    }
}
