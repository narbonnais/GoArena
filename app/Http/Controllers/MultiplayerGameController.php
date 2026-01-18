<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponses;
use App\Models\MultiplayerGame;
use App\Models\TimeControl;
use App\Services\MultiplayerGameService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MultiplayerGameController extends Controller
{
    use ApiResponses;

    public function __construct(
        private MultiplayerGameService $gameService
    ) {}

    /**
     * Show the multiplayer lobby page.
     */
    public function lobby(Request $request): Response
    {
        $user = $request->user();
        $timeControls = TimeControl::all();

        // Get user's ratings for all board sizes
        $ratings = [];
        if ($user) {
            foreach ([9, 13, 19] as $boardSize) {
                $rating = $user->getRatingForBoardSize($boardSize);
                if ($rating) {
                    $ratings[$boardSize] = [
                        'rating' => $rating->rating,
                        'rank_title' => $rating->rank_title,
                        'games_played' => $rating->games_played,
                        'wins' => $rating->wins,
                        'losses' => $rating->losses,
                        'win_rate' => $rating->win_rate,
                    ];
                }
            }
        }

        // Get active game for the user
        $activeGame = null;
        if ($user) {
            $game = MultiplayerGame::where(function ($q) use ($user) {
                $q->where('black_player_id', $user->id)
                    ->orWhere('white_player_id', $user->id);
            })
                ->whereIn('status', [MultiplayerGame::STATUS_PENDING, MultiplayerGame::STATUS_ACTIVE])
                ->with(['blackPlayer:id,name', 'whitePlayer:id,name'])
                ->first();

            if ($game) {
                $activeGame = [
                    'id' => $game->id,
                    'opponent' => $game->black_player_id === $user->id
                        ? $game->whitePlayer?->name
                        : $game->blackPlayer?->name,
                    'board_size' => $game->board_size,
                    'your_color' => $game->black_player_id === $user->id ? 'black' : 'white',
                ];
            }
        }

        return Inertia::render('go/Multiplayer', [
            'timeControls' => $timeControls->map(fn ($tc) => [
                'id' => $tc->id,
                'name' => $tc->name,
                'slug' => $tc->slug,
                'display_time' => $tc->display_time,
                'initial_time_seconds' => $tc->initial_time_seconds,
                'increment_seconds' => $tc->increment_seconds,
            ]),
            'ratings' => $ratings,
            'activeGame' => $activeGame,
        ]);
    }

    /**
     * Show a multiplayer game page.
     */
    public function show(Request $request, MultiplayerGame $game): Response
    {
        $user = $request->user();

        // Start the game if both players are present and it's pending
        if ($game->status === MultiplayerGame::STATUS_PENDING && $game->isParticipant($user)) {
            $this->gameService->startGame($game);
            $game->refresh();
        }

        $gameState = $this->gameService->getGameState($game);
        $playerColor = $game->getPlayerColor($user);

        return Inertia::render('go/MultiplayerGame', [
            'game' => $gameState,
            'playerColor' => $playerColor,
            'isSpectator' => $playerColor === null,
        ]);
    }

    /**
     * Show a game for spectating.
     */
    public function spectate(Request $request, MultiplayerGame $game): Response
    {
        $gameState = $this->gameService->getGameState($game);

        return Inertia::render('go/SpectateGame', [
            'game' => $gameState,
            'isSpectator' => true,
        ]);
    }

    /**
     * Get the current game state (API).
     */
    public function state(MultiplayerGame $game): JsonResponse
    {
        return $this->successResponse([
            'game' => $this->gameService->getGameState($game),
        ]);
    }

    /**
     * Play a move.
     */
    public function move(Request $request, MultiplayerGame $game): JsonResponse
    {
        $maxCoord = $game->board_size - 1;
        $validated = $request->validate([
            'x' => "required|integer|min:0|max:{$maxCoord}",
            'y' => "required|integer|min:0|max:{$maxCoord}",
        ]);

        $result = $this->gameService->playMove(
            $game,
            $request->user(),
            ['x' => $validated['x'], 'y' => $validated['y']]
        );

        if (! $result['success']) {
            return $this->errorResponse($result['error']);
        }

        return $this->successResponse($result);
    }

    /**
     * Pass turn.
     */
    public function pass(Request $request, MultiplayerGame $game): JsonResponse
    {
        $result = $this->gameService->pass($game, $request->user());

        if (! $result['success']) {
            return $this->errorResponse($result['error']);
        }

        return $this->successResponse($result);
    }

    /**
     * Resign from the game.
     */
    public function resign(Request $request, MultiplayerGame $game): JsonResponse
    {
        $result = $this->gameService->resign($game, $request->user());

        if (! $result['success']) {
            return $this->errorResponse($result['error']);
        }

        return $this->successResponse($result);
    }

    /**
     * Accept score and end the game.
     */
    public function acceptScore(Request $request, MultiplayerGame $game): JsonResponse
    {
        $result = $this->gameService->acceptScore(
            $game,
            $request->user()
        );

        if (! $result['success']) {
            return $this->errorResponse($result['error']);
        }

        return $this->successResponse($result);
    }

    /**
     * Toggle a dead stone during scoring.
     */
    public function toggleDeadStone(Request $request, MultiplayerGame $game): JsonResponse
    {
        $maxCoord = $game->board_size - 1;
        $validated = $request->validate([
            'x' => "required|integer|min:0|max:{$maxCoord}",
            'y' => "required|integer|min:0|max:{$maxCoord}",
        ]);

        $result = $this->gameService->toggleDeadStone(
            $game,
            $request->user(),
            ['x' => $validated['x'], 'y' => $validated['y']]
        );

        if (! $result['success']) {
            return $this->errorResponse($result['error']);
        }

        return $this->successResponse($result);
    }

    /**
     * Get list of live games.
     */
    public function liveGames(): JsonResponse
    {
        $games = MultiplayerGame::with(['blackPlayer', 'whitePlayer', 'timeControl'])
            ->active()
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($game) => [
                'id' => $game->id,
                'black_player' => [
                    'id' => $game->blackPlayer->id,
                    'name' => $game->blackPlayer->display_name_or_name,
                    'rating' => $game->black_rating_before,
                ],
                'white_player' => [
                    'id' => $game->whitePlayer->id,
                    'name' => $game->whitePlayer->display_name_or_name,
                    'rating' => $game->white_rating_before,
                ],
                'board_size' => $game->board_size,
                'time_control' => [
                    'name' => $game->timeControl->name,
                    'display_time' => $game->timeControl->display_time,
                ],
                'move_count' => $game->move_count,
                'current_player' => $game->current_player,
            ]);

        return $this->successResponse(['games' => $games]);
    }
}
