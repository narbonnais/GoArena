<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

class Lesson extends Model
{
    use HasFactory;

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (Lesson $lesson) {
            // Only check circular dependencies if prerequisites are being changed
            if ($lesson->isDirty('prerequisites') && ! empty($lesson->prerequisites)) {
                // Ensure we have an ID (for updates) or skip validation (for new records)
                if ($lesson->exists && $lesson->wouldCreateCircularDependency($lesson->prerequisites)) {
                    throw ValidationException::withMessages([
                        'prerequisites' => ['Circular dependency detected in lesson prerequisites.'],
                    ]);
                }
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'slug',
        'title',
        'description',
        'category',
        'difficulty',
        'duration',
        'order',
        'prerequisites',
        'steps',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order' => 'integer',
            'prerequisites' => 'array',
            'steps' => 'array',
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
     * Get the progress records for this lesson.
     */
    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    /**
     * Get the user's progress for this lesson.
     */
    public function userProgress(?User $user): ?LessonProgress
    {
        if (! $user) {
            return null;
        }

        return $this->progress()->where('user_id', $user->id)->first();
    }

    /**
     * Get the total number of steps in this lesson.
     */
    public function getTotalStepsAttribute(): int
    {
        return count($this->steps ?? []);
    }

    /**
     * Check if a user has completed this lesson.
     */
    public function isCompletedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->progress()
            ->where('user_id', $user->id)
            ->where('completed', true)
            ->exists();
    }

    /**
     * Check if a lesson is locked for a user (prerequisites not completed).
     *
     * @param  User|null  $user  The user to check
     * @param  array|null  $completedLessonIds  Optional pre-loaded array of completed lesson IDs to avoid N+1 queries
     */
    public function isLockedFor(?User $user, ?array $completedLessonIds = null): bool
    {
        if (empty($this->prerequisites)) {
            return false;
        }

        if (! $user) {
            return true;
        }

        // Use provided completed IDs or fetch from database
        if ($completedLessonIds === null) {
            $completedLessonIds = LessonProgress::where('user_id', $user->id)
                ->where('completed', true)
                ->pluck('lesson_id')
                ->toArray();
        }

        // Check if all prerequisites are completed
        foreach ($this->prerequisites as $prerequisiteId) {
            if (! in_array($prerequisiteId, $completedLessonIds)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if setting the given prerequisites would create a circular dependency.
     *
     * Uses DFS to detect cycles in the prerequisite graph.
     *
     * @param  array  $newPrerequisites  The proposed prerequisite IDs
     * @return bool True if circular dependency would be created
     */
    public function wouldCreateCircularDependency(array $newPrerequisites): bool
    {
        if (empty($newPrerequisites)) {
            return false;
        }

        // Check if this lesson is in its own prerequisites
        if (in_array($this->id, $newPrerequisites)) {
            return true;
        }

        // Build prerequisite map for all lessons
        $allLessons = self::whereNotNull('prerequisites')->get(['id', 'prerequisites']);
        $prereqMap = [];
        foreach ($allLessons as $lesson) {
            $prereqMap[$lesson->id] = $lesson->prerequisites ?? [];
        }

        // Temporarily set this lesson's prerequisites to the new ones
        $prereqMap[$this->id] = $newPrerequisites;

        // DFS to detect cycle starting from this lesson
        $visited = [];
        $recursionStack = [];

        return $this->hasCycleDFS($this->id, $prereqMap, $visited, $recursionStack);
    }

    /**
     * DFS helper to detect cycles in prerequisite graph.
     */
    private function hasCycleDFS(int $lessonId, array $prereqMap, array &$visited, array &$recursionStack): bool
    {
        $visited[$lessonId] = true;
        $recursionStack[$lessonId] = true;

        $prerequisites = $prereqMap[$lessonId] ?? [];
        foreach ($prerequisites as $prereqId) {
            // If not visited, recurse
            if (! isset($visited[$prereqId])) {
                if ($this->hasCycleDFS($prereqId, $prereqMap, $visited, $recursionStack)) {
                    return true;
                }
            }
            // If in current recursion stack, we found a cycle
            elseif (isset($recursionStack[$prereqId]) && $recursionStack[$prereqId]) {
                return true;
            }
        }

        $recursionStack[$lessonId] = false;

        return false;
    }
}
