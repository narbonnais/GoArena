<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $user_id
 * @property string $title
 * @property string|null $description
 * @property int $board_size
 * @property float $komi
 * @property array $move_tree
 * @property int|null $source_game_id
 * @property bool $is_public
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read User $user
 * @property-read Game|null $sourceGame
 * @property-read int $move_count
 */
class AnalysisStudy extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'board_size',
        'komi',
        'move_tree',
        'source_game_id',
        'is_public',
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
            'move_tree' => 'array',
            'is_public' => 'boolean',
        ];
    }

    /**
     * Get the user that owns the study.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the source game if this study was created from a game.
     */
    public function sourceGame(): BelongsTo
    {
        return $this->belongsTo(Game::class, 'source_game_id');
    }

    /**
     * Get the move count from the tree (counts non-root nodes).
     */
    public function getMoveCountAttribute(): int
    {
        $tree = $this->move_tree;
        if (! $tree || ! isset($tree['nodes'])) {
            return 0;
        }

        // Count nodes excluding root
        return max(0, count($tree['nodes']) - 1);
    }

    /**
     * Scope a query to only include public studies.
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope a query to only include private studies.
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('is_public', false);
    }
}
