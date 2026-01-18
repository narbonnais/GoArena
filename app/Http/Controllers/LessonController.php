<?php

namespace App\Http\Controllers;

use App\Models\Lesson;
use App\Models\LessonProgress;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class LessonController extends Controller
{
    /**
     * Display a listing of all lessons with user progress.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Eager load progress for current user to avoid N+1 queries
        $lessons = Lesson::query()
            ->when($user, function ($query) use ($user) {
                $query->with(['progress' => fn ($q) => $q->where('user_id', $user->id)]);
            })
            ->orderBy('order')
            ->get();

        // Extract completed lesson IDs from already-loaded progress (no additional query)
        $completedLessonIds = $user
            ? $lessons->flatMap->progress->filter->completed->pluck('lesson_id')->toArray()
            : [];

        $mappedLessons = $lessons->map(function (Lesson $lesson) use ($user, $completedLessonIds) {
            // Use eager-loaded progress
            $progress = $lesson->progress->first();
            $isCompleted = $progress?->completed ?? false;

            // Check prerequisites using pre-fetched completed lessons
            $isLocked = false;
            if (! empty($lesson->prerequisites)) {
                if (! $user) {
                    $isLocked = true;
                } else {
                    foreach ($lesson->prerequisites as $prerequisiteId) {
                        if (! in_array($prerequisiteId, $completedLessonIds)) {
                            $isLocked = true;
                            break;
                        }
                    }
                }
            }

            return [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'category' => $lesson->category,
                'difficulty' => $lesson->difficulty,
                'duration' => $lesson->duration,
                'order' => $lesson->order,
                'completed' => $isCompleted,
                'locked' => $isLocked,
                'current_step' => $progress?->current_step ?? 0,
                'total_steps' => $lesson->total_steps,
            ];
        });

        $completedCount = $mappedLessons->filter(fn ($l) => $l['completed'])->count();

        return Inertia::render('go/Learn', [
            'lessons' => $mappedLessons,
            'completedCount' => $completedCount,
        ]);
    }

    /**
     * Display the specified lesson for learning.
     */
    public function show(Request $request, Lesson $lesson): Response
    {
        $user = $request->user();

        // Check if lesson is locked
        if ($lesson->isLockedFor($user)) {
            return Inertia::render('go/Learn', [
                'error' => 'This lesson is locked. Please complete the prerequisite lessons first.',
            ]);
        }

        // Get or create progress for authenticated users
        $progress = null;
        if ($user) {
            $progress = LessonProgress::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'current_step' => 0,
                    'started_at' => now(),
                ]
            );
        }

        return Inertia::render('go/LessonDetail', [
            'lesson' => [
                'id' => $lesson->id,
                'slug' => $lesson->slug,
                'title' => $lesson->title,
                'description' => $lesson->description,
                'category' => $lesson->category,
                'difficulty' => $lesson->difficulty,
                'duration' => $lesson->duration,
                'order' => $lesson->order,
                'prerequisites' => $lesson->prerequisites ?? [],
                'steps' => $lesson->steps ?? [],
            ],
            'progress' => $progress ? [
                'lesson_id' => $progress->lesson_id,
                'completed' => $progress->completed,
                'current_step' => $progress->current_step,
                'started_at' => $progress->started_at?->toISOString(),
                'completed_at' => $progress->completed_at?->toISOString(),
            ] : null,
        ]);
    }

    /**
     * Update the user's progress on a lesson.
     */
    public function updateProgress(Request $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required');
        }

        $totalSteps = count($lesson->steps ?? []);

        $validated = $request->validate([
            'current_step' => "required|integer|min:0|max:{$totalSteps}",
        ]);

        DB::transaction(function () use ($user, $lesson, $validated) {
            LessonProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'current_step' => $validated['current_step'],
                    'started_at' => now(),
                ]
            );
        });

        return redirect()->back();
    }

    /**
     * Mark a lesson as completed.
     */
    public function complete(Request $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Authentication required');
        }

        // Validate lesson has steps before allowing completion
        if (empty($lesson->steps)) {
            abort(422, 'Cannot complete a lesson with no steps');
        }

        DB::transaction(function () use ($user, $lesson) {
            LessonProgress::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'lesson_id' => $lesson->id,
                ],
                [
                    'completed' => true,
                    'current_step' => count($lesson->steps ?? []),
                    'completed_at' => now(),
                ]
            );
        });

        return redirect()->back();
    }
}
