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
 * @property int $rating
 * @property int $games_played
 * @property int $wins
 * @property int $losses
 * @property int $draws
 * @property int $peak_rating
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read User $user
 * @property-read string $rank_title
 */
class UserRating extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'board_size',
        'rating',
        'games_played',
        'wins',
        'losses',
        'draws',
        'peak_rating',
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
            'games_played' => 'integer',
            'wins' => 'integer',
            'losses' => 'integer',
            'draws' => 'integer',
            'peak_rating' => 'integer',
        ];
    }

    /**
     * Get the user that owns this rating.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the rank title based on ELO rating.
     * Go ranks: 30k (beginner) -> 1k -> 1d -> 9d (pro)
     * Uses EGF-standard mapping where 1 dan = 2100 rating.
     */
    public function getRankTitleAttribute(): string
    {
        $rating = $this->rating;

        // Dan ranks (2100+)
        if ($rating >= 2900) {
            return '9d';
        }
        if ($rating >= 2800) {
            return '8d';
        }
        if ($rating >= 2700) {
            return '7d';
        }
        if ($rating >= 2600) {
            return '6d';
        }
        if ($rating >= 2500) {
            return '5d';
        }
        if ($rating >= 2400) {
            return '4d';
        }
        if ($rating >= 2300) {
            return '3d';
        }
        if ($rating >= 2200) {
            return '2d';
        }
        if ($rating >= 2100) {
            return '1d';
        }

        // Single-digit kyu (SDK: 1k-9k, 1200-2099)
        if ($rating >= 2000) {
            return '1k';
        }
        if ($rating >= 1900) {
            return '2k';
        }
        if ($rating >= 1800) {
            return '3k';
        }
        if ($rating >= 1700) {
            return '4k';
        }
        if ($rating >= 1600) {
            return '5k';
        }
        if ($rating >= 1500) {
            return '6k';
        }
        if ($rating >= 1400) {
            return '7k';
        }
        if ($rating >= 1300) {
            return '8k';
        }
        if ($rating >= 1200) {
            return '9k';
        }

        // Double-digit kyu (DDK: 10k-30k, <1200)
        if ($rating >= 1100) {
            return '10k';
        }
        if ($rating >= 1000) {
            return '12k';
        }
        if ($rating >= 900) {
            return '15k';
        }
        if ($rating >= 800) {
            return '17k';
        }
        if ($rating >= 700) {
            return '18k';  // Initial rating
        }
        if ($rating >= 600) {
            return '20k';
        }
        if ($rating >= 500) {
            return '22k';
        }
        if ($rating >= 400) {
            return '24k';
        }
        if ($rating >= 300) {
            return '26k';
        }
        if ($rating >= 200) {
            return '28k';
        }

        return '30k';  // Absolute beginner
    }

    /**
     * Get win rate as a percentage.
     */
    public function getWinRateAttribute(): float
    {
        if ($this->games_played === 0) {
            return 0.0;
        }

        return round(($this->wins / $this->games_played) * 100, 1);
    }

    /**
     * Scope a query to a specific board size.
     */
    public function scopeForBoardSize(Builder $query, int $boardSize): Builder
    {
        return $query->where('board_size', $boardSize);
    }

    /**
     * Scope a query to order by rating descending (for leaderboard).
     */
    public function scopeLeaderboard(Builder $query): Builder
    {
        return $query->orderByDesc('rating');
    }
}
