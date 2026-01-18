<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $board_size
 * @property int $time_control_id
 * @property int $rating
 * @property int $max_rating_diff
 * @property bool $is_ranked
 * @property \Illuminate\Support\Carbon $joined_at
 * @property \Illuminate\Support\Carbon $expires_at
 * @property-read User $user
 * @property-read TimeControl $timeControl
 */
class MatchmakingQueue extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'matchmaking_queue';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'board_size',
        'time_control_id',
        'rating',
        'max_rating_diff',
        'is_ranked',
        'joined_at',
        'expires_at',
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
            'rating' => 'integer',
            'max_rating_diff' => 'integer',
            'is_ranked' => 'boolean',
            'joined_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user in queue.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the time control.
     */
    public function timeControl(): BelongsTo
    {
        return $this->belongsTo(TimeControl::class);
    }

    /**
     * Scope to find active (non-expired) queue entries.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope to match criteria for finding opponents.
     */
    public function scopeMatchCriteria(Builder $query, int $boardSize, int $timeControlId, bool $isRanked): Builder
    {
        return $query->where('board_size', $boardSize)
            ->where('time_control_id', $timeControlId)
            ->where('is_ranked', $isRanked);
    }

    /**
     * Check if this entry can match with another based on rating range.
     */
    public function canMatchWith(MatchmakingQueue $other): bool
    {
        // Both must accept each other's rating
        $ratingDiff = abs($this->rating - $other->rating);

        return $ratingDiff <= $this->max_rating_diff && $ratingDiff <= $other->max_rating_diff;
    }

    /**
     * Get wait time in seconds.
     */
    public function getWaitTimeSecondsAttribute(): int
    {
        return (int) $this->joined_at->diffInSeconds(now());
    }
}
