<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $initial_time_seconds
 * @property int $increment_seconds
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MultiplayerGame> $multiplayerGames
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MatchmakingQueue> $queueEntries
 * @property-read string $display_time
 */
class TimeControl extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'initial_time_seconds',
        'increment_seconds',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initial_time_seconds' => 'integer',
            'increment_seconds' => 'integer',
        ];
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get games using this time control.
     */
    public function multiplayerGames(): HasMany
    {
        return $this->hasMany(MultiplayerGame::class);
    }

    /**
     * Get queue entries using this time control.
     */
    public function queueEntries(): HasMany
    {
        return $this->hasMany(MatchmakingQueue::class);
    }

    /**
     * Get the display time string (e.g., "10min + 5s").
     */
    public function getDisplayTimeAttribute(): string
    {
        $initial = $this->initial_time_seconds;
        $increment = $this->increment_seconds;

        // Format initial time
        if ($initial >= 3600) {
            $hours = floor($initial / 3600);
            $minutes = ($initial % 3600) / 60;
            $initialStr = $minutes > 0 ? "{$hours}h{$minutes}min" : "{$hours}h";
        } elseif ($initial >= 60) {
            $minutes = $initial / 60;
            $initialStr = "{$minutes}min";
        } else {
            $initialStr = "{$initial}s";
        }

        // Format increment
        if ($increment > 0) {
            return "{$initialStr} + {$increment}s";
        }

        return $initialStr;
    }

    /**
     * Get initial time in milliseconds.
     */
    public function getInitialTimeMsAttribute(): int
    {
        return $this->initial_time_seconds * 1000;
    }
}
