<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponses;
use App\Models\MultiplayerGame;
use App\Models\TimeControl;
use App\Services\MatchmakingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MatchmakingController extends Controller
{
    use ApiResponses;

    public function __construct(
        private MatchmakingService $matchmakingService
    ) {}

    /**
     * Join the matchmaking queue.
     */
    public function join(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'board_size' => 'required|integer|in:9,13,19',
            'time_control_slug' => 'required|string|exists:time_controls,slug',
            'is_ranked' => 'boolean',
            'max_rating_diff' => 'integer|min:50|max:500',
        ]);

        $user = $request->user();

        // Check if user already in an active game
        $activeGame = $user->multiplayerGamesAsBlack()
            ->whereIn('status', ['pending', 'active'])
            ->orWhere(function ($q) use ($user) {
                $q->where('white_player_id', $user->id)
                    ->whereIn('status', ['pending', 'active']);
            })
            ->first();

        if ($activeGame) {
            return $this->errorResponse(
                'You are already in an active game',
                400,
                'ALREADY_IN_GAME'
            );
        }

        $timeControl = TimeControl::where('slug', $validated['time_control_slug'])->firstOrFail();

        $entry = $this->matchmakingService->joinQueue(
            user: $user,
            boardSize: $validated['board_size'],
            timeControl: $timeControl,
            isRanked: $validated['is_ranked'] ?? true,
            maxRatingDiff: $validated['max_rating_diff'] ?? 200
        );

        if (app()->environment('local')) {
            $games = $this->matchmakingService->processQueue();
            $matchedGame = collect($games)->first(
                fn (MultiplayerGame $game) => $game->black_player_id === $user->id || $game->white_player_id === $user->id
            );

            if ($matchedGame) {
                return $this->successResponse([
                    'match_found' => $this->buildMatchFoundPayload($matchedGame, $user->id),
                ]);
            }
        }

        return $this->successResponse([
            'queue_entry' => [
                'board_size' => $entry->board_size,
                'time_control' => $timeControl,
                'rating' => $entry->rating,
                'max_rating_diff' => $entry->max_rating_diff,
                'is_ranked' => $entry->is_ranked,
                'joined_at' => $entry->joined_at->toIso8601String(),
                'expires_at' => $entry->expires_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Leave the matchmaking queue.
     */
    public function leave(Request $request): JsonResponse
    {
        $removed = $this->matchmakingService->leaveQueue($request->user());

        if (! $removed) {
            return $this->successResponse(['message' => 'Not in matchmaking queue']);
        }

        return $this->successResponse(['message' => 'Left matchmaking queue']);
    }

    /**
     * Get current matchmaking status.
     */
    public function status(Request $request): JsonResponse
    {
        $status = $this->matchmakingService->getQueueStatus($request->user());

        if (! $status) {
            return $this->successResponse([
                'in_queue' => false,
            ]);
        }

        return $this->successResponse($status);
    }

    /**
     * Get available time controls.
     */
    public function timeControls(): JsonResponse
    {
        $controls = TimeControl::all()->map(fn ($tc) => [
            'id' => $tc->id,
            'name' => $tc->name,
            'slug' => $tc->slug,
            'display_time' => $tc->display_time,
            'initial_time_seconds' => $tc->initial_time_seconds,
            'increment_seconds' => $tc->increment_seconds,
        ]);

        return $this->successResponse(['time_controls' => $controls]);
    }

    /**
     * Build match payload for a user from a game.
     *
     * @return array<string, mixed>
     */
    private function buildMatchFoundPayload(MultiplayerGame $game, int $userId): array
    {
        $game->loadMissing(['blackPlayer', 'whitePlayer']);

        $yourColor = $game->black_player_id === $userId ? 'black' : 'white';
        $opponent = $yourColor === 'black' ? $game->whitePlayer : $game->blackPlayer;

        return [
            'game_id' => $game->id,
            'opponent' => [
                'id' => $opponent->id,
                'name' => $opponent->display_name_or_name,
                'rating' => $yourColor === 'black' ? $game->white_rating_before : $game->black_rating_before,
            ],
            'your_color' => $yourColor,
        ];
    }
}
