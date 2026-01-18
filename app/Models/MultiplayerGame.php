<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

/**
 * @property int $id
 * @property int $black_player_id
 * @property int $white_player_id
 * @property int $board_size
 * @property int $time_control_id
 * @property float $komi
 * @property bool $is_ranked
 * @property string $status
 * @property string $score_phase
 * @property string $current_player
 * @property int $black_time_remaining_ms
 * @property int $white_time_remaining_ms
 * @property \Illuminate\Support\Carbon|null $last_move_at
 * @property string|null $winner
 * @property string|null $end_reason
 * @property array $move_history
 * @property int $move_count
 * @property array $captures
 * @property array $dead_stones
 * @property array $score_acceptance
 * @property array|null $scores
 * @property int|null $black_rating_before
 * @property int|null $black_rating_after
 * @property int|null $white_rating_before
 * @property int|null $white_rating_after
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $blackPlayer
 * @property-read User $whitePlayer
 * @property-read TimeControl $timeControl
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GameConnection> $connections
 */
class MultiplayerGame extends Game
{
    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_FINISHED = 'finished';

    public const STATUS_ABANDONED = 'abandoned';

    /**
     * Scoring phase constants.
     */
    public const SCORE_PHASE_NONE = 'none';

    public const SCORE_PHASE_MARKING = 'marking';

    /**
     * End reason constants.
     */
    public const END_SCORE = 'score';

    public const END_RESIGNATION = 'resignation';

    public const END_TIMEOUT = 'timeout';

    public const END_ABANDONMENT = 'abandonment';

    /**
     * Set defaults and enforce the human game type.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('human', function (Builder $query) {
            $query->where('game_type', 'human');
        });

        static::creating(function (self $game) {
            $game->game_type = 'human';
            $game->user_id = $game->user_id ?? $game->black_player_id;
            $game->ai_level = $game->ai_level ?: 'human';
            $game->status = $game->status ?? self::STATUS_PENDING;
            $game->score_phase = $game->score_phase ?? self::SCORE_PHASE_NONE;
            $game->move_history = $game->move_history ?? [];
            $game->move_count = $game->move_count ?? (is_array($game->move_history) ? count($game->move_history) : 0);
            $game->black_captures = $game->black_captures ?? 0;
            $game->white_captures = $game->white_captures ?? 0;
            $game->black_score = $game->black_score ?? 0;
            $game->white_score = $game->white_score ?? 0;
            $game->duration_seconds = $game->duration_seconds ?? 0;
            $game->is_finished = in_array($game->status, [self::STATUS_FINISHED, self::STATUS_ABANDONED], true);
        });

        static::saving(function (self $game) {
            $game->game_type = 'human';
            $game->user_id = $game->user_id ?? $game->black_player_id;
            $game->ai_level = $game->ai_level ?: 'human';
            $game->move_history = $game->move_history ?? [];
            if ($game->move_count === null) {
                $game->move_count = is_array($game->move_history) ? count($game->move_history) : 0;
            }
            $game->black_captures = $game->black_captures ?? 0;
            $game->white_captures = $game->white_captures ?? 0;
            $game->black_score = $game->black_score ?? 0;
            $game->white_score = $game->white_score ?? 0;
            $game->duration_seconds = $game->duration_seconds ?? 0;
            $game->is_finished = in_array($game->status, [self::STATUS_FINISHED, self::STATUS_ABANDONED], true);
        });
    }

    /**
     * Check if a user is a participant in this game.
     */
    public function isParticipant(User $user): bool
    {
        return $this->black_player_id === $user->id || $this->white_player_id === $user->id;
    }

    /**
     * Get the player color for a user.
     */
    public function getPlayerColor(User $user): ?string
    {
        if ($this->black_player_id === $user->id) {
            return 'black';
        }
        if ($this->white_player_id === $user->id) {
            return 'white';
        }

        return null;
    }

    /**
     * Check if it's this user's turn.
     */
    public function isPlayerTurn(User $user): bool
    {
        return $this->getPlayerColor($user) === $this->current_player;
    }

    /**
     * Get the opponent of a user.
     */
    public function getOpponent(User $user): ?User
    {
        if ($this->black_player_id === $user->id) {
            return $this->whitePlayer;
        }
        if ($this->white_player_id === $user->id) {
            return $this->blackPlayer;
        }

        return null;
    }

    /**
     * Get time remaining for a color.
     */
    public function getTimeRemaining(string $color): int
    {
        return $color === 'black' ? $this->black_time_remaining_ms : $this->white_time_remaining_ms;
    }

    /**
     * Check if the game is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if the game is finished.
     */
    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_FINISHED, self::STATUS_ABANDONED]);
    }

    /**
     * Scope to only include active games.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to only include finished games.
     */
    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_FINISHED, self::STATUS_ABANDONED]);
    }

    /**
     * Scope to only include games for a specific user.
     */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('black_player_id', $user->id)
                ->orWhere('white_player_id', $user->id);
        });
    }

    /**
     * Scope to only include ranked games.
     */
    public function scopeRanked(Builder $query): Builder
    {
        return $query->where('is_ranked', true);
    }
}
