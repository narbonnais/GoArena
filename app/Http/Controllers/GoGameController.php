<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponses;
use App\Models\Game;
use App\Models\GoGame;
use App\Services\GameHistoryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class GoGameController extends Controller
{
    use ApiResponses, AuthorizesRequests;

    /**
     * Display the home page with user stats and recent games.
     */
    public function home(Request $request, GameHistoryService $historyService): Response
    {
        $totalUsers = \App\Models\User::count();

        $recentGames = [];
        if ($request->user()) {
            $recentGames = $historyService->getRecentGames($request->user(), 3);
        }

        return Inertia::render('go/Index', [
            'totalUsers' => $totalUsers,
            'recentGames' => $recentGames,
        ]);
    }

    /**
     * Display a listing of the user's games.
     */
    public function index(Request $request, GameHistoryService $historyService): Response
    {
        $ongoingGames = $historyService->getOngoingGames($request->user());
        $finishedGames = $historyService->getFinishedGames($request->user(), 10);

        return Inertia::render('go/History', [
            'ongoingGames' => $ongoingGames,
            'games' => $finishedGames,
        ]);
    }

    /**
     * Display the specified game for replay.
     */
    public function show(Request $request, Game $goGame): Response
    {
        $this->authorize('view', $goGame);

        return Inertia::render('go/Replay', [
            'game' => $this->formatGameForReplay($goGame, $request->user()),
        ]);
    }

    /**
     * Resume an ongoing game.
     */
    public function resume(Request $request, GoGame $goGame): Response
    {
        $this->authorize('view', $goGame);

        // Only allow resuming non-finished games
        if ($goGame->is_finished) {
            return Inertia::render('go/Replay', [
                'game' => $this->formatGameForReplay($goGame, $request->user()),
            ]);
        }

        return Inertia::render('go/Play', [
            'boardSize' => $goGame->board_size,
            'resumeGame' => [
                'id' => $goGame->id,
                'board_size' => $goGame->board_size,
                'komi' => $goGame->komi,
                'move_count' => $goGame->move_count,
                'black_captures' => $goGame->black_captures,
                'white_captures' => $goGame->white_captures,
                'move_history' => $goGame->validated_move_history,
                'duration_seconds' => $goGame->duration_seconds,
            ],
        ]);
    }

    /**
     * Store a newly completed or in-progress game.
     */
    public function store(Request $request): JsonResponse
    {
        $boardSize = (int) $request->input('board_size', 0);
        $maxCoord = max(0, $boardSize - 1);

        $validated = $request->validate(
            $this->gameValidationRules($maxCoord, true, true)
        );

        // Validate move_count matches actual move_history length
        if ($validated['move_count'] !== count($validated['move_history'])) {
            return $this->validationErrorResponse('move_count does not match move_history length');
        }

        $game = DB::transaction(function () use ($request, $validated) {
            return $request->user()->goGames()->create($validated);
        });

        return $this->successResponse(['game_id' => $game->id], 201);
    }

    /**
     * Update an existing game (for in-progress games).
     */
    public function update(Request $request, GoGame $goGame): JsonResponse
    {
        $this->authorize('update', $goGame);

        // Use existing game's board_size for coordinate bounds
        $maxCoord = $goGame->board_size - 1;

        $validated = $request->validate(array_merge(
            ['expected_updated_at' => 'nullable|date'],
            $this->gameValidationRules($maxCoord, false, false)
        ));

        // Optimistic locking: check if game was modified since client last fetched it
        if (isset($validated['expected_updated_at'])) {
            $expectedTime = \Carbon\Carbon::parse($validated['expected_updated_at']);
            if (! $goGame->updated_at->eq($expectedTime)) {
                return $this->errorResponse(
                    'Game was modified by another request. Please refresh and try again.',
                    409,
                    'CONFLICT'
                );
            }
            unset($validated['expected_updated_at']);
        }

        // Validate move_count matches actual move_history length
        if ($validated['move_count'] !== count($validated['move_history'])) {
            return $this->validationErrorResponse('move_count does not match move_history length');
        }

        DB::transaction(function () use ($goGame, $validated) {
            $goGame->update($validated);
        });

        return $this->successResponse([
            'game_id' => $goGame->id,
            'updated_at' => $goGame->fresh()->updated_at->toISOString(),
        ]);
    }

    /**
     * Format a game for replay display.
     */
    private function formatGameForReplay(Game $goGame, ?\App\Models\User $user): array
    {
        $gameType = $goGame->getGameType();
        $userColor = $user ? $goGame->getUserColor($user) : null;
        $durationSeconds = (int) ($goGame->duration_seconds ?? 0);

        if ($durationSeconds === 0 && $goGame->created_at) {
            $endAt = $goGame->last_move_at ?? $goGame->updated_at;
            if ($endAt) {
                $durationSeconds = $goGame->created_at->diffInSeconds($endAt);
            }
        }

        if ($gameType === 'human') {
            $goGame->loadMissing(['blackPlayer', 'whitePlayer']);
        }

        $blackPlayer = [
            'id' => $goGame->black_player_id ?? ($gameType === 'bot' ? $goGame->user_id : null),
            'name' => $gameType === 'human'
                ? ($goGame->blackPlayer?->display_name_or_name ?? 'Black')
                : ($user?->display_name_or_name ?? 'You'),
        ];

        $whitePlayer = [
            'id' => $goGame->white_player_id,
            'name' => $gameType === 'human'
                ? ($goGame->whitePlayer?->display_name_or_name ?? 'White')
                : 'KataGo',
        ];

        return [
            'id' => $goGame->id,
            'game_type' => $gameType,
            'board_size' => $goGame->board_size,
            'komi' => (float) $goGame->komi,
            'winner' => $goGame->winner,
            'end_reason' => $goGame->end_reason,
            'score_margin' => $goGame->score_margin !== null ? (float) $goGame->score_margin : null,
            'move_count' => $goGame->move_count,
            'black_score' => $goGame->black_score !== null ? (float) $goGame->black_score : 0.0,
            'white_score' => $goGame->white_score !== null ? (float) $goGame->white_score : 0.0,
            'black_captures' => (int) ($goGame->black_captures ?? 0),
            'white_captures' => (int) ($goGame->white_captures ?? 0),
            'move_history' => $goGame->validated_move_history,
            'duration_seconds' => $durationSeconds,
            'user_won' => $user ? $goGame->userWonFor($user) : false,
            'user_color' => $userColor,
            'black_player' => $blackPlayer,
            'white_player' => $whitePlayer,
            'created_at' => $goGame->created_at->toISOString(),
        ];
    }

    /**
     * Get validation rules for game payloads.
     *
     * @return array<string, mixed>
     */
    private function gameValidationRules(int $maxCoord, bool $includeBoardSize, bool $includeKomi): array
    {
        $rules = [
            'winner' => 'required_if:is_finished,true|nullable|string|in:black,white,draw',
            'end_reason' => 'required_if:is_finished,true|nullable|string|in:score,resignation',
            'score_margin' => 'nullable|numeric|min:-999|max:999',
            'move_count' => 'required|integer|min:0|max:400',
            'black_score' => 'required|numeric|min:0|max:9999',
            'white_score' => 'required|numeric|min:0|max:9999',
            'black_captures' => 'required|integer|min:0|max:361',
            'white_captures' => 'required|integer|min:0|max:361',
            'move_history' => 'required|array|max:400',
            'move_history.*.stone' => 'required|string|in:black,white',
            'move_history.*.coordinate' => 'nullable|array',
            'move_history.*.coordinate.x' => "required_with:move_history.*.coordinate|integer|min:0|max:{$maxCoord}",
            'move_history.*.coordinate.y' => "required_with:move_history.*.coordinate|integer|min:0|max:{$maxCoord}",
            'move_history.*.captures' => 'nullable|array|max:50',
            'move_history.*.captures.*.x' => "required|integer|min:0|max:{$maxCoord}",
            'move_history.*.captures.*.y' => "required|integer|min:0|max:{$maxCoord}",
            'duration_seconds' => 'required|integer|min:0|max:86400',
            'is_finished' => 'required|boolean',
        ];

        if ($includeBoardSize) {
            $rules['board_size'] = 'required|integer|in:9,13,19';
        }

        if ($includeKomi) {
            $rules['komi'] = 'required|numeric|min:-50|max:50';
        }

        return $rules;
    }

    /**
     * Remove the specified game from history.
     */
    public function destroy(Request $request, GoGame $goGame): RedirectResponse
    {
        $this->authorize('delete', $goGame);

        $goGame->delete();

        return redirect()->route('go.history')->with('success', 'Game deleted successfully.');
    }
}
