<?php

namespace App\Services;

use App\Events\GameEndedEvent;
use App\Events\GameMoveEvent;
use App\Events\GameScoringEvent;
use App\Models\MultiplayerGame;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MultiplayerGameService
{
    public function __construct(
        private GameClockService $clockService,
        private EloService $eloService,
        private GoScoringService $scoringService,
        private GoRulesService $rulesService
    ) {}

    /**
     * Play a move in the game.
     *
     * @param  array{x: int, y: int}|null  $coordinate
     * @return array{success: bool, error?: string, move?: array}
     */
    public function playMove(MultiplayerGame $game, User $player, ?array $coordinate): array
    {
        // Validate game state
        if (! $game->isInProgress()) {
            return ['success' => false, 'error' => 'Game is not in progress'];
        }

        if ($game->score_phase === MultiplayerGame::SCORE_PHASE_MARKING) {
            return ['success' => false, 'error' => 'Game is in scoring phase'];
        }

        // Validate player turn
        if (! $game->isPlayerTurn($player)) {
            return ['success' => false, 'error' => 'Not your turn'];
        }

        // Check for timeout
        $timedOut = $this->clockService->checkTimeout($game);
        if ($timedOut) {
            $this->endGameByTimeout($game, $timedOut);

            return ['success' => false, 'error' => 'Time expired'];
        }

        $result = DB::transaction(function () use ($game, $coordinate) {
            $state = $this->rulesService->replayMoves($game->board_size, $game->move_history);
            $board = $state['board'];
            $koPoint = $state['ko_point'];
            $captureCounts = $state['captures'];

            // Update clock
            $clockUpdate = $this->clockService->updateClock($game);

            // Create move record
            $moveNumber = $game->move_count + 1;
            $stone = $game->current_player;
            $captures = [];

            if ($coordinate !== null) {
                if (! isset($coordinate['x'], $coordinate['y'])) {
                    return ['success' => false, 'error' => 'Invalid coordinate'];
                }

                $x = (int) $coordinate['x'];
                $y = (int) $coordinate['y'];
                if (! $this->rulesService->isValidCoordinate($board, $x, $y)) {
                    return ['success' => false, 'error' => 'Invalid coordinate'];
                }

                $moveResult = $this->rulesService->placeStone($board, ['x' => $x, 'y' => $y], $stone, $koPoint);
                if (! $moveResult['valid']) {
                    return ['success' => false, 'error' => $moveResult['error'] ?? 'Invalid move'];
                }

                $captures = $moveResult['captures'];
                $captureCounts[$stone] = ($captureCounts[$stone] ?? 0) + count($captures);
            }

            $move = [
                'coordinate' => $coordinate,
                'stone' => $stone,
                'captures' => $captures,
                'moveNumber' => $moveNumber,
            ];

            // Update game state
            $moveHistory = $game->move_history;
            $moveHistory[] = $move;

            $passPass = $this->hasConsecutivePasses($moveHistory);
            $scorePhase = $game->score_phase ?? MultiplayerGame::SCORE_PHASE_NONE;
            $deadStones = $game->dead_stones ?? [];
            $scoreAcceptance = $game->score_acceptance ?? ['black' => false, 'white' => false];

            if ($passPass && $scorePhase !== MultiplayerGame::SCORE_PHASE_MARKING) {
                $scorePhase = MultiplayerGame::SCORE_PHASE_MARKING;
                $deadStones = [];
                $scoreAcceptance = ['black' => false, 'white' => false];
            }

            // Switch current player
            $nextPlayer = $stone === 'black' ? 'white' : 'black';

            $game->update([
                'move_history' => $moveHistory,
                'move_count' => $moveNumber,
                'current_player' => $nextPlayer,
                'captures' => $captureCounts,
                'score_phase' => $scorePhase,
                'dead_stones' => $deadStones,
                'score_acceptance' => $scoreAcceptance,
                $clockUpdate['time_field'] => $clockUpdate['new_time'],
                'last_move_at' => now(),
            ]);

            return [
                'success' => true,
                'move' => $move,
                'times' => [
                    'black_time_remaining_ms' => $game->black_time_remaining_ms,
                    'white_time_remaining_ms' => $game->white_time_remaining_ms,
                ],
                'pass_pass' => $passPass,
                'move_history' => $moveHistory,
                'score_phase' => $scorePhase,
                'dead_stones' => $deadStones,
                'score_acceptance' => $scoreAcceptance,
            ];
        });

        if (! $result['success']) {
            return ['success' => false, 'error' => $result['error'] ?? 'Invalid move'];
        }

        // Broadcast move event after commit (don't fail the request if broadcast fails)
        try {
            broadcast(new GameMoveEvent(
                gameId: $game->id,
                coordinate: $result['move']['coordinate'],
                stone: $result['move']['stone'],
                moveNumber: $result['move']['moveNumber'],
                blackTimeRemainingMs: $result['times']['black_time_remaining_ms'],
                whiteTimeRemainingMs: $result['times']['white_time_remaining_ms'],
                captures: $result['move']['captures']
            ));
        } catch (\Throwable $e) {
            report($e);
        }

        $scorePayload = null;
        if ($result['pass_pass'] && $result['score_phase'] === MultiplayerGame::SCORE_PHASE_MARKING) {
            $game->refresh();
            $score = $this->calculateScoreWithDeadStones($game, $result['dead_stones']);
            $scorePayload = $score['scores'];

            try {
                broadcast(new GameScoringEvent(
                    gameId: $game->id,
                    scorePhase: $result['score_phase'],
                    deadStones: $result['dead_stones'],
                    scoreAcceptance: $result['score_acceptance'],
                    scores: $score['scores']
                ));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return [
            'success' => true,
            'move' => $result['move'],
            'times' => $result['times'],
            'score_phase' => $result['score_phase'],
            'dead_stones' => $result['dead_stones'],
            'score_acceptance' => $result['score_acceptance'],
            'scores' => $scorePayload,
        ];
    }

    /**
     * Player passes their turn.
     */
    public function pass(MultiplayerGame $game, User $player): array
    {
        return $this->playMove($game, $player, null);
    }

    /**
     * Player resigns the game.
     */
    public function resign(MultiplayerGame $game, User $player): array
    {
        if (! $game->isInProgress()) {
            return ['success' => false, 'error' => 'Game is not in progress'];
        }

        if (! $game->isParticipant($player)) {
            return ['success' => false, 'error' => 'You are not a participant in this game'];
        }

        $playerColor = $game->getPlayerColor($player);
        $winner = $playerColor === 'black' ? 'white' : 'black';

        return $this->endGame($game, $winner, MultiplayerGame::END_RESIGNATION);
    }

    /**
     * Toggle a dead stone during scoring phase.
     *
     * @param  array{x: int, y: int}  $coordinate
     */
    public function toggleDeadStone(MultiplayerGame $game, User $player, array $coordinate): array
    {
        if (! $game->isInProgress()) {
            return ['success' => false, 'error' => 'Game is not in progress'];
        }

        if (! $game->isParticipant($player)) {
            return ['success' => false, 'error' => 'You are not a participant in this game'];
        }

        if ($game->score_phase !== MultiplayerGame::SCORE_PHASE_MARKING) {
            return ['success' => false, 'error' => 'Game is not in scoring phase'];
        }

        if (! isset($coordinate['x'], $coordinate['y'])) {
            return ['success' => false, 'error' => 'Invalid coordinate'];
        }

        $x = (int) $coordinate['x'];
        $y = (int) $coordinate['y'];

        $state = $this->rulesService->replayMoves($game->board_size, $game->move_history);
        $board = $state['board'];

        if (! $this->rulesService->isValidCoordinate($board, $x, $y)) {
            return ['success' => false, 'error' => 'Invalid coordinate'];
        }

        if ($board[$y][$x] === null) {
            return ['success' => false, 'error' => 'No stone at coordinate'];
        }

        $deadStones = $this->normalizeDeadStones($game->dead_stones ?? []);
        $key = "{$x},{$y}";
        $updated = [];
        $removed = false;

        foreach ($deadStones as $deadStone) {
            $deadKey = "{$deadStone['x']},{$deadStone['y']}";
            if ($deadKey === $key) {
                $removed = true;

                continue;
            }
            $updated[] = $deadStone;
        }

        if (! $removed) {
            $updated[] = ['x' => $x, 'y' => $y];
        }

        $scoreAcceptance = ['black' => false, 'white' => false];

        $game->update([
            'dead_stones' => $updated,
            'score_acceptance' => $scoreAcceptance,
        ]);

        $score = $this->calculateScoreWithDeadStones($game, $updated);

        try {
            broadcast(new GameScoringEvent(
                gameId: $game->id,
                scorePhase: $game->score_phase,
                deadStones: $updated,
                scoreAcceptance: $scoreAcceptance,
                scores: $score['scores']
            ));
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'success' => true,
            'dead_stones' => $updated,
            'score_acceptance' => $scoreAcceptance,
            'scores' => $score['scores'],
        ];
    }

    /**
     * Accept the current dead-stone marking.
     */
    public function acceptScore(MultiplayerGame $game, User $player): array
    {
        if (! $game->isInProgress()) {
            return ['success' => false, 'error' => 'Game is not in progress'];
        }

        if (! $game->isParticipant($player)) {
            return ['success' => false, 'error' => 'You are not a participant in this game'];
        }

        if ($game->score_phase !== MultiplayerGame::SCORE_PHASE_MARKING) {
            return ['success' => false, 'error' => 'Game is not in scoring phase'];
        }

        $playerColor = $game->getPlayerColor($player);
        if ($playerColor === null) {
            return ['success' => false, 'error' => 'You are not a participant in this game'];
        }

        $scoreAcceptance = $game->score_acceptance ?? ['black' => false, 'white' => false];
        $scoreAcceptance[$playerColor] = true;

        $game->update([
            'score_acceptance' => $scoreAcceptance,
        ]);

        $score = $this->calculateScoreWithDeadStones($game, $game->dead_stones ?? []);

        if (! empty($scoreAcceptance['black']) && ! empty($scoreAcceptance['white'])) {
            return $this->endGame($game, $score['winner'], MultiplayerGame::END_SCORE, $score['scores']);
        }

        try {
            broadcast(new GameScoringEvent(
                gameId: $game->id,
                scorePhase: $game->score_phase,
                deadStones: $game->dead_stones ?? [],
                scoreAcceptance: $scoreAcceptance,
                scores: $score['scores']
            ));
        } catch (\Throwable $e) {
            report($e);
        }

        return [
            'success' => true,
            'score_acceptance' => $scoreAcceptance,
            'scores' => $score['scores'],
        ];
    }

    /**
     * End game due to timeout.
     */
    public function endGameByTimeout(MultiplayerGame $game, string $timedOutPlayer): array
    {
        $winner = $timedOutPlayer === 'black' ? 'white' : 'black';

        return $this->endGame($game, $winner, MultiplayerGame::END_TIMEOUT);
    }

    /**
     * End game due to abandonment.
     */
    public function endGameByAbandonment(MultiplayerGame $game, string $abandonedPlayer): array
    {
        $winner = $abandonedPlayer === 'black' ? 'white' : 'black';

        return $this->endGame($game, $winner, MultiplayerGame::END_ABANDONMENT);
    }

    /**
     * End the game with a result.
     */
    private function endGame(MultiplayerGame $game, string $winner, string $endReason, ?array $scores = null): array
    {
        return DB::transaction(function () use ($game, $winner, $endReason, $scores) {
            $scoreMargin = null;
            if (is_array($scores) && isset($scores['black'], $scores['white'])) {
                $scoreMargin = round(abs($scores['black'] - $scores['white']), 1);
            }

            $durationSeconds = $game->duration_seconds ?? 0;
            if ($durationSeconds === 0 && $game->created_at) {
                $durationSeconds = $game->created_at->diffInSeconds(now());
            }

            $game->update([
                'status' => MultiplayerGame::STATUS_FINISHED,
                'winner' => $winner,
                'end_reason' => $endReason,
                'scores' => $scores,
                'score_margin' => $scoreMargin,
                'duration_seconds' => $durationSeconds,
            ]);

            // Apply rating changes if ranked
            if ($game->is_ranked) {
                $this->eloService->applyRatingChanges($game);
                $game->refresh();
            }

            // Broadcast game ended event (don't fail if broadcast fails)
            try {
                broadcast(new GameEndedEvent(
                    gameId: $game->id,
                    winner: $winner,
                    endReason: $endReason,
                    scores: $scores,
                    ratingChanges: $game->is_ranked ? [
                        'black' => [
                            'before' => $game->black_rating_before,
                            'after' => $game->black_rating_after,
                        ],
                        'white' => [
                            'before' => $game->white_rating_before,
                            'after' => $game->white_rating_after,
                        ],
                    ] : null
                ));
            } catch (\Throwable $e) {
                report($e);
            }

            return [
                'success' => true,
                'winner' => $winner,
                'end_reason' => $endReason,
                'scores' => $scores,
            ];
        });
    }

    /**
     * Check if the last two moves were passes.
     *
     * @param  array<int, array<string, mixed>>  $moveHistory
     */
    private function hasConsecutivePasses(array $moveHistory): bool
    {
        if (count($moveHistory) < 2) {
            return false;
        }

        $lastTwo = array_slice($moveHistory, -2);

        return $this->isPassMove($lastTwo[0]) && $this->isPassMove($lastTwo[1]);
    }

    /**
     * Determine whether a move is a pass.
     *
     * @param  array<string, mixed>  $move
     */
    private function isPassMove(array $move): bool
    {
        return ! array_key_exists('coordinate', $move) || $move['coordinate'] === null;
    }

    /**
     * Calculate Japanese territory score with dead stones removed.
     *
     * @param  array<int, array{x: int, y: int}>  $deadStones
     * @return array{scores: array{black: float, white: float}, winner: string, territory: array{black: int, white: int}, stones: array{black: int, white: int}}
     */
    private function calculateScoreWithDeadStones(MultiplayerGame $game, array $deadStones): array
    {
        $board = $this->scoringService->buildBoardState($game->board_size, $game->move_history);
        $captures = $this->scoringService->countCapturesFromMoveHistory($game->move_history);
        $normalized = $this->normalizeDeadStones($deadStones);

        foreach ($normalized as $stoneCoord) {
            $x = $stoneCoord['x'];
            $y = $stoneCoord['y'];
            $stone = $board[$y][$x] ?? null;

            if ($stone === 'black') {
                $captures['white'] = ($captures['white'] ?? 0) + 1;
            } elseif ($stone === 'white') {
                $captures['black'] = ($captures['black'] ?? 0) + 1;
            }
        }

        $board = $this->rulesService->removeStones($board, $normalized);

        return $this->scoringService->calculateScoreFromBoard($board, (float) $game->komi, $captures);
    }

    /**
     * Normalize and deduplicate dead stones list.
     *
     * @param  array<int, array{x: int, y: int}>  $deadStones
     * @return array<int, array{x: int, y: int}>
     */
    private function normalizeDeadStones(array $deadStones): array
    {
        $normalized = [];
        $seen = [];

        foreach ($deadStones as $deadStone) {
            if (! is_array($deadStone) || ! isset($deadStone['x'], $deadStone['y'])) {
                continue;
            }

            $x = (int) $deadStone['x'];
            $y = (int) $deadStone['y'];
            $key = "{$x},{$y}";

            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $normalized[] = ['x' => $x, 'y' => $y];
        }

        return $normalized;
    }

    /**
     * Start a game (change status from pending to active).
     */
    public function startGame(MultiplayerGame $game): bool
    {
        if ($game->status !== MultiplayerGame::STATUS_PENDING) {
            return false;
        }

        // Initialize ratings if ranked
        if ($game->is_ranked) {
            $blackRating = $game->blackPlayer->getOrCreateRating($game->board_size);
            $whiteRating = $game->whitePlayer->getOrCreateRating($game->board_size);

            $game->update([
                'status' => MultiplayerGame::STATUS_ACTIVE,
                'last_move_at' => now(),
                'black_rating_before' => $blackRating->rating,
                'white_rating_before' => $whiteRating->rating,
            ]);
        } else {
            $game->update([
                'status' => MultiplayerGame::STATUS_ACTIVE,
                'last_move_at' => now(),
            ]);
        }

        return true;
    }

    /**
     * Get the full game state for API response.
     */
    public function getGameState(MultiplayerGame $game): array
    {
        $game->load(['blackPlayer', 'whitePlayer', 'timeControl']);

        $times = $this->clockService->getCurrentTimes($game);
        $scorePhase = $game->score_phase ?? MultiplayerGame::SCORE_PHASE_NONE;
        $deadStones = $this->normalizeDeadStones($game->dead_stones ?? []);
        $scoreAcceptance = $game->score_acceptance ?? ['black' => false, 'white' => false];
        $provisionalScores = null;

        if ($scorePhase === MultiplayerGame::SCORE_PHASE_MARKING) {
            $score = $this->calculateScoreWithDeadStones($game, $deadStones);
            $provisionalScores = $score['scores'];
        }

        return [
            'id' => $game->id,
            'black_player' => $this->formatPlayer($game->blackPlayer, $game->black_rating_before),
            'white_player' => $this->formatPlayer($game->whitePlayer, $game->white_rating_before),
            'board_size' => $game->board_size,
            'time_control' => [
                'id' => $game->timeControl->id,
                'name' => $game->timeControl->name,
                'slug' => $game->timeControl->slug,
                'display_time' => $game->timeControl->display_time,
                'initial_time_seconds' => $game->timeControl->initial_time_seconds,
                'increment_seconds' => $game->timeControl->increment_seconds,
            ],
            'komi' => $game->komi,
            'is_ranked' => $game->is_ranked,
            'status' => $game->status,
            'score_phase' => $scorePhase,
            'current_player' => $game->current_player,
            'black_time_remaining_ms' => $times['black_time_remaining_ms'],
            'white_time_remaining_ms' => $times['white_time_remaining_ms'],
            'last_move_at' => $game->last_move_at?->toIso8601String(),
            'winner' => $game->winner,
            'end_reason' => $game->end_reason,
            'move_history' => $game->move_history,
            'move_count' => $game->move_count,
            'captures' => $game->captures,
            'dead_stones' => $deadStones,
            'score_acceptance' => $scoreAcceptance,
            'provisional_scores' => $provisionalScores,
            'scores' => $game->scores,
            'black_rating_before' => $game->black_rating_before,
            'black_rating_after' => $game->black_rating_after,
            'white_rating_before' => $game->white_rating_before,
            'white_rating_after' => $game->white_rating_after,
            'server_time' => $times['server_time'],
            'created_at' => $game->created_at->toIso8601String(),
        ];
    }

    /**
     * Format player info for API response.
     */
    private function formatPlayer(User $user, ?int $rating): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'display_name' => $user->display_name,
            'avatar_url' => $user->avatar_url,
            'country_code' => $user->country_code,
            'rating' => $rating,
        ];
    }
}
