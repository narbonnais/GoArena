<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string|null $display_name
 * @property string|null $avatar_url
 * @property string|null $country_code
 * @property bool $is_online
 * @property \Illuminate\Support\Carbon|null $last_seen_at
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property \Illuminate\Support\Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, GoGame> $goGames
 * @property-read \Illuminate\Database\Eloquent\Collection<int, AnalysisStudy> $analysisStudies
 * @property-read \Illuminate\Database\Eloquent\Collection<int, LessonProgress> $lessonProgress
 * @property-read \Illuminate\Database\Eloquent\Collection<int, UserRating> $ratings
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MultiplayerGame> $multiplayerGamesAsBlack
 * @property-read \Illuminate\Database\Eloquent\Collection<int, MultiplayerGame> $multiplayerGamesAsWhite
 * @property-read MatchmakingQueue|null $matchmakingEntry
 * @property-read string $display_name_or_name
 */
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'display_name',
        'avatar_url',
        'country_code',
        'is_online',
        'last_seen_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_online' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    /**
     * Get the user's Go games.
     */
    public function goGames(): HasMany
    {
        return $this->hasMany(GoGame::class);
    }

    /**
     * Get the user's analysis studies.
     */
    public function analysisStudies(): HasMany
    {
        return $this->hasMany(AnalysisStudy::class);
    }

    /**
     * Get the user's lesson progress records.
     */
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Get the user's ratings for all board sizes.
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(UserRating::class);
    }

    /**
     * Get the user's multiplayer games as black.
     */
    public function multiplayerGamesAsBlack(): HasMany
    {
        return $this->hasMany(MultiplayerGame::class, 'black_player_id');
    }

    /**
     * Get the user's multiplayer games as white.
     */
    public function multiplayerGamesAsWhite(): HasMany
    {
        return $this->hasMany(MultiplayerGame::class, 'white_player_id');
    }

    /**
     * Get the user's current matchmaking entry.
     */
    public function matchmakingEntry(): HasOne
    {
        return $this->hasOne(MatchmakingQueue::class);
    }

    /**
     * Get the user's rating for a specific board size.
     */
    public function getRatingForBoardSize(int $boardSize): ?UserRating
    {
        return $this->ratings()->where('board_size', $boardSize)->first();
    }

    /**
     * Get or create a rating for a specific board size.
     * New players start at 700 (18 kyu) per EGF standard.
     */
    public function getOrCreateRating(int $boardSize): UserRating
    {
        return $this->ratings()->firstOrCreate(
            ['board_size' => $boardSize],
            ['rating' => 700, 'peak_rating' => 700]
        );
    }

    /**
     * Get display name or fallback to name.
     */
    public function getDisplayNameOrNameAttribute(): string
    {
        return $this->display_name ?? $this->name;
    }

    /**
     * Check if user is in the matchmaking queue.
     */
    public function isInQueue(): bool
    {
        return $this->matchmakingEntry()->active()->exists();
    }
}
