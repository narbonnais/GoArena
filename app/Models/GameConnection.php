<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $game_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon $connected_at
 * @property \Illuminate\Support\Carbon|null $last_ping_at
 * @property \Illuminate\Support\Carbon|null $disconnected_at
 * @property-read MultiplayerGame $game
 * @property-read User $user
 */
class GameConnection extends Model
{
    use HasFactory;

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
        'game_id',
        'user_id',
        'connected_at',
        'last_ping_at',
        'disconnected_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'last_ping_at' => 'datetime',
            'disconnected_at' => 'datetime',
        ];
    }

    /**
     * Get the multiplayer game.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(MultiplayerGame::class, 'game_id');
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the connection is currently active.
     */
    public function isConnected(): bool
    {
        return is_null($this->disconnected_at);
    }

    /**
     * Check if the connection is stale (no ping for more than 30 seconds).
     */
    public function isStale(int $thresholdSeconds = 30): bool
    {
        if (! $this->isConnected()) {
            return true;
        }

        $lastActivity = $this->last_ping_at ?? $this->connected_at;

        return $lastActivity->diffInSeconds(now()) > $thresholdSeconds;
    }

    /**
     * Get the disconnection duration in seconds.
     */
    public function getDisconnectionDurationAttribute(): ?int
    {
        if (! $this->disconnected_at) {
            return null;
        }

        return (int) $this->disconnected_at->diffInSeconds(now());
    }

    /**
     * Scope to only include active connections.
     */
    public function scopeConnected(Builder $query): Builder
    {
        return $query->whereNull('disconnected_at');
    }

    /**
     * Scope to only include disconnected connections.
     */
    public function scopeDisconnected(Builder $query): Builder
    {
        return $query->whereNotNull('disconnected_at');
    }
}
