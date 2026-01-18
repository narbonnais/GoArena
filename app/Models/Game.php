<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int|null $user_id
 * @property int|null $black_player_id
 * @property int|null $white_player_id
 * @property int $board_size
 * @property int|null $time_control_id
 * @property string|null $ai_level
 * @property float $komi
 * @property string|null $game_type
 * @property bool $is_ranked
 * @property string|null $status
 * @property string|null $score_phase
 * @property string|null $current_player
 * @property int|null $black_time_remaining_ms
 * @property int|null $white_time_remaining_ms
 * @property \Illuminate\Support\Carbon|null $last_move_at
 * @property string|null $winner
 * @property string|null $end_reason
 * @property float|null $score_margin
 * @property int $move_count
 * @property float|null $black_score
 * @property float|null $white_score
 * @property int|null $black_captures
 * @property int|null $white_captures
 * @property array $move_history
 * @property int|null $duration_seconds
 * @property bool $is_finished
 * @property array $dead_stones
 * @property array $score_acceptance
 * @property int|null $black_rating_before
 * @property int|null $black_rating_after
 * @property int|null $white_rating_before
 * @property int|null $white_rating_after
 * @property int|null $legacy_multiplayer_id
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read User|null $blackPlayer
 * @property-read User|null $whitePlayer
 * @property-read TimeControl|null $timeControl
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GameConnection> $connections
 */
class Game extends AbstractGame
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'go_games';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'black_player_id',
        'white_player_id',
        'board_size',
        'time_control_id',
        'ai_level',
        'komi',
        'game_type',
        'is_ranked',
        'status',
        'score_phase',
        'current_player',
        'black_time_remaining_ms',
        'white_time_remaining_ms',
        'last_move_at',
        'winner',
        'end_reason',
        'score_margin',
        'move_count',
        'black_score',
        'white_score',
        'black_captures',
        'white_captures',
        'move_history',
        'duration_seconds',
        'is_finished',
        'dead_stones',
        'score_acceptance',
        'black_rating_before',
        'black_rating_after',
        'white_rating_before',
        'white_rating_after',
        'legacy_multiplayer_id',
        'captures',
        'scores',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'board_size' => 'integer',
            'komi' => 'decimal:1',
            'game_type' => 'string',
            'is_ranked' => 'boolean',
            'status' => 'string',
            'score_phase' => 'string',
            'current_player' => 'string',
            'black_time_remaining_ms' => 'integer',
            'white_time_remaining_ms' => 'integer',
            'last_move_at' => 'datetime',
            'score_margin' => 'decimal:1',
            'move_count' => 'integer',
            'black_score' => 'decimal:1',
            'white_score' => 'decimal:1',
            'black_captures' => 'integer',
            'white_captures' => 'integer',
            'move_history' => 'array',
            'duration_seconds' => 'integer',
            'is_finished' => 'boolean',
            'dead_stones' => 'array',
            'score_acceptance' => 'array',
            'black_rating_before' => 'integer',
            'black_rating_after' => 'integer',
            'white_rating_before' => 'integer',
            'white_rating_after' => 'integer',
            'legacy_multiplayer_id' => 'integer',
        ];
    }

    /**
     * Get the user that owns the game (bot games).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the black player (human games).
     */
    public function blackPlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'black_player_id');
    }

    /**
     * Get the white player (human games).
     */
    public function whitePlayer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'white_player_id');
    }

    /**
     * Get the time control (human games).
     */
    public function timeControl(): BelongsTo
    {
        return $this->belongsTo(TimeControl::class);
    }

    /**
     * Get the game connections (human games).
     */
    public function connections(): HasMany
    {
        return $this->hasMany(GameConnection::class);
    }

    /**
     * Accessor for captures as a unified array.
     *
     * @return array{black: int, white: int}
     */
    public function getCapturesAttribute(): array
    {
        return [
            'black' => (int) ($this->black_captures ?? 0),
            'white' => (int) ($this->white_captures ?? 0),
        ];
    }

    /**
     * Mutator for captures to keep legacy array APIs working.
     *
     * @param  array{black?: int, white?: int}|null  $captures
     */
    public function setCapturesAttribute(?array $captures): void
    {
        $captures = is_array($captures) ? $captures : [];
        $this->attributes['black_captures'] = (int) ($captures['black'] ?? 0);
        $this->attributes['white_captures'] = (int) ($captures['white'] ?? 0);
    }

    /**
     * Accessor for final scores (black/white totals).
     *
     * @return array{black: float, white: float}|null
     */
    public function getScoresAttribute(): ?array
    {
        if (! $this->hasFinalScores()) {
            return null;
        }

        return [
            'black' => (float) $this->black_score,
            'white' => (float) $this->white_score,
        ];
    }

    /**
     * Mutator for final scores (black/white totals).
     *
     * @param  array{black?: float, white?: float}|null  $scores
     */
    public function setScoresAttribute(?array $scores): void
    {
        if (! is_array($scores)) {
            $this->attributes['black_score'] = $this->attributes['black_score'] ?? 0;
            $this->attributes['white_score'] = $this->attributes['white_score'] ?? 0;

            return;
        }

        $this->attributes['black_score'] = isset($scores['black']) ? (float) $scores['black'] : null;
        $this->attributes['white_score'] = isset($scores['white']) ? (float) $scores['white'] : null;
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
        return $color === 'black'
            ? (int) ($this->black_time_remaining_ms ?? 0)
            : (int) ($this->white_time_remaining_ms ?? 0);
    }

    /**
     * Check if the game is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->getStatus() === 'active';
    }

    /**
     * Check if the game is finished.
     */
    public function isFinished(): bool
    {
        return in_array($this->getStatus(), ['finished', 'abandoned'], true);
    }

    /**
     * Scope to only include active games.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to only include finished games.
     */
    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereIn('status', ['finished', 'abandoned']);
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

    /**
     * Get validated move history with fallback to empty array.
     * Ensures each move has required structure even if data is corrupted.
     */
    public function getValidatedMoveHistoryAttribute(): array
    {
        $history = $this->move_history;

        if (! is_array($history)) {
            return [];
        }

        return array_values(array_filter($history, function ($move) {
            if (! isset($move['stone']) || ! in_array($move['stone'], ['black', 'white'])) {
                return false;
            }

            if (isset($move['coordinate']) && $move['coordinate'] !== null) {
                if (! isset($move['coordinate']['x']) || ! isset($move['coordinate']['y'])) {
                    return false;
                }
                if (! is_int($move['coordinate']['x']) || ! is_int($move['coordinate']['y'])) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Get the unified game type.
     */
    public function getGameType(): string
    {
        return $this->game_type ?? 'bot';
    }

    /**
     * Get the unified game status.
     */
    public function getStatus(): string
    {
        if ($this->status) {
            return $this->status;
        }

        return $this->is_finished ? 'finished' : 'active';
    }

    /**
     * Get the user's color for this game.
     */
    public function getUserColor(User $user): ?string
    {
        if ($this->getGameType() === 'human') {
            return $this->getPlayerColor($user);
        }

        $ownerId = $this->black_player_id ?? $this->user_id;

        return $ownerId === $user->id ? 'black' : null;
    }

    /**
     * Get the opponent name for this game.
     */
    public function getOpponentName(User $user): string
    {
        if ($this->getGameType() !== 'human') {
            return 'KataGo';
        }

        $this->loadMissing(['blackPlayer', 'whitePlayer']);

        $opponent = $this->black_player_id === $user->id
            ? $this->whitePlayer
            : $this->blackPlayer;

        return $opponent?->display_name_or_name ?? 'Opponent';
    }

    /**
     * Get the resume URL for this game.
     */
    public function getResumeUrl(User $user): ?string
    {
        if ($this->getGameType() !== 'human') {
            return $this->isFinished() ? null : route('go.play.resume', $this);
        }

        if (! $this->isParticipant($user)) {
            return null;
        }

        if (! in_array($this->getStatus(), ['pending', 'active'], true)) {
            return null;
        }

        return route('multiplayer.show', $this);
    }

    /**
     * Get the detail URL for this game.
     */
    public function getDetailUrl(User $user): string
    {
        return route('go.replay', $this);
    }

    /**
     * Get scores with komi applied, if available.
     *
     * @return array{black: float, white: float}|null
     */
    public function getScores(): ?array
    {
        if (! $this->hasFinalScores()) {
            return null;
        }

        return [
            'black' => (float) $this->black_score,
            'white' => (float) $this->white_score,
        ];
    }

    /**
     * Determine whether the user can delete the game.
     */
    public function canDelete(User $user): bool
    {
        return $this->getGameType() === 'bot' && $this->user_id === $user->id;
    }

    /**
     * Get the delete URL for the game.
     */
    public function getDeleteUrl(User $user): ?string
    {
        if (! $this->canDelete($user)) {
            return null;
        }

        return route('go.games.destroy', $this);
    }

    private function hasFinalScores(): bool
    {
        if (! $this->isFinished()) {
            return false;
        }

        if ($this->end_reason !== 'score') {
            return false;
        }

        return $this->black_score !== null && $this->white_score !== null;
    }
}
