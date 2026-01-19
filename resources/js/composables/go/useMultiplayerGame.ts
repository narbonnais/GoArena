import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import { createEmptyBoard, isValidCoordinate, removeStones, setStone } from '@/lib/go/board';
import type { BoardState, Coordinate, Stone } from '@/types/go';
import type {
    ClockSyncPayload,
    GameEndedPayload,
    GameMovePayload,
    GameScoringPayload,
    MultiplayerGame,
    PlayerConnectionPayload,
} from '@/types/multiplayer';

export interface UseMultiplayerGameOptions {
    game: MultiplayerGame;
    playerColor: Stone | null;
    onGameEnded?: (payload: GameEndedPayload) => void;
}

export function useMultiplayerGame(options: UseMultiplayerGameOptions) {
    const game = ref<MultiplayerGame>(options.game);
    const playerColor = ref<Stone | null>(options.playerColor);
    const isSpectator = computed(() => playerColor.value === null);

    const isMyTurn = computed(
        () => !isSpectator.value && game.value.current_player === playerColor.value
    );
    const isLoading = ref(false);
    const error = ref<string | null>(null);

    // Time tracking
    const blackTimeRemaining = ref(game.value.black_time_remaining_ms);
    const whiteTimeRemaining = ref(game.value.white_time_remaining_ms);
    let clockInterval: ReturnType<typeof setInterval> | null = null;

    // Connection status
    const opponentConnected = ref(true);

    // Echo channels
    let gameChannel: unknown = null;

    // Computed board state from move history
    const boardSize = computed(() => game.value.board_size);
    const board = computed<BoardState>(() => {
        const boardState = createEmptyBoard(game.value.board_size);
        const history = Array.isArray(game.value.move_history) ? game.value.move_history : [];

        let nextState = boardState;
        for (const move of history) {
            if (!move) continue;

            if (Array.isArray(move.captures) && move.captures.length > 0) {
                const validCaptures = move.captures.filter((coord) =>
                    isValidCoordinate(nextState, coord)
                );
                if (validCaptures.length > 0) {
                    nextState = removeStones(nextState, validCaptures);
                }
            }

            if (move.coordinate && isValidCoordinate(nextState, move.coordinate)) {
                nextState = setStone(nextState, move.coordinate, move.stone);
            }
        }

        return nextState;
    });

    // Get last move for highlighting
    const lastMove = computed(() => {
        const history = game.value.move_history;
        if (history.length === 0) return null;
        return history[history.length - 1];
    });

    // Game status
    const isGameOver = computed(() => game.value.status === 'finished' || game.value.status === 'abandoned');
    const winner = computed(() => game.value.winner);
    const scorePhase = computed(() => game.value.score_phase ?? 'none');
    const isScoring = computed(() => scorePhase.value === 'marking');
    const deadStones = computed(() =>
        Array.isArray(game.value.dead_stones) ? game.value.dead_stones : []
    );
    const scoreAcceptance = computed(() => game.value.score_acceptance ?? { black: false, white: false });
    const provisionalScores = computed(() => game.value.provisional_scores ?? null);

    // Format time for display
    function formatTime(ms: number): string {
        const totalSeconds = Math.floor(ms / 1000);
        const minutes = Math.floor(totalSeconds / 60);
        const seconds = totalSeconds % 60;
        return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
    }

    const blackTimeFormatted = computed(() => formatTime(blackTimeRemaining.value));
    const whiteTimeFormatted = computed(() => formatTime(whiteTimeRemaining.value));

    // Start local clock countdown
    function startClock(): void {
        if (isScoring.value) {
            stopClock();
            return;
        }

        stopClock();
        clockInterval = setInterval(() => {
            if (isGameOver.value || isScoring.value) {
                stopClock();
                return;
            }

            if (game.value.current_player === 'black') {
                blackTimeRemaining.value = Math.max(0, blackTimeRemaining.value - 100);
            } else {
                whiteTimeRemaining.value = Math.max(0, whiteTimeRemaining.value - 100);
            }
        }, 100);
    }

    function stopClock(): void {
        if (clockInterval) {
            clearInterval(clockInterval);
            clockInterval = null;
        }
    }

    // Sync clock with server
    function syncClock(payload: ClockSyncPayload): void {
        blackTimeRemaining.value = payload.black_time_remaining_ms;
        whiteTimeRemaining.value = payload.white_time_remaining_ms;
    }

    // API calls
    async function playMove(coordinate: Coordinate): Promise<boolean> {
        if (isScoring.value || !isMyTurn.value || isLoading.value) return false;

        error.value = null;
        isLoading.value = true;

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(`/api/multiplayer/${game.value.id}/move`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ x: coordinate.x, y: coordinate.y }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to play move');
            }

            const fallbackPayload = buildMovePayloadFromResponse(data);
            if (fallbackPayload) {
                applyMove(fallbackPayload);
            }
            applyScorePhaseUpdate(data);

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to play move';
            return false;
        } finally {
            isLoading.value = false;
        }
    }

    async function pass(): Promise<boolean> {
        if (isScoring.value || !isMyTurn.value || isLoading.value) return false;

        error.value = null;
        isLoading.value = true;

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(`/api/multiplayer/${game.value.id}/pass`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to pass');
            }

            const fallbackPayload = buildMovePayloadFromResponse(data);
            if (fallbackPayload) {
                applyMove(fallbackPayload);
            }
            applyScorePhaseUpdate(data);

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to pass';
            return false;
        } finally {
            isLoading.value = false;
        }
    }

    async function toggleDeadStone(coordinate: Coordinate): Promise<boolean> {
        if (isSpectator.value || !isScoring.value || isLoading.value) return false;

        error.value = null;
        isLoading.value = true;

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(`/api/multiplayer/${game.value.id}/dead-stones/toggle`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ x: coordinate.x, y: coordinate.y }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to update dead stones');
            }

            if (Array.isArray(data.dead_stones)) {
                game.value.dead_stones = data.dead_stones;
            }
            if (data.score_acceptance) {
                game.value.score_acceptance = data.score_acceptance;
            }
            if (data.scores) {
                game.value.provisional_scores = data.scores;
            }
            if (data.winner && data.end_reason) {
                handleGameEnded(data as GameEndedPayload);
            }

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to update dead stones';
            return false;
        } finally {
            isLoading.value = false;
        }
    }

    async function acceptScore(): Promise<boolean> {
        if (isSpectator.value || !isScoring.value || isLoading.value) return false;

        error.value = null;
        isLoading.value = true;

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(`/api/multiplayer/${game.value.id}/accept-score`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to accept score');
            }

            if (data.score_acceptance) {
                game.value.score_acceptance = data.score_acceptance;
            }
            if (data.scores) {
                game.value.provisional_scores = data.scores;
            }

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to accept score';
            return false;
        } finally {
            isLoading.value = false;
        }
    }

    async function resign(): Promise<boolean> {
        if (isSpectator.value || isLoading.value) return false;

        error.value = null;
        isLoading.value = true;

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch(`/api/multiplayer/${game.value.id}/resign`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Failed to resign');
            }

            if (data.winner && data.end_reason) {
                handleGameEnded(data as GameEndedPayload);
            }

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to resign';
            return false;
        } finally {
            isLoading.value = false;
        }
    }

    // Handle incoming move from WebSocket
    function handleMove(payload: GameMovePayload): void {
        console.log('[WS] handleMove called with:', payload);
        applyMove(payload);
    }

    function handleScoring(payload: GameScoringPayload): void {
        game.value.score_phase = payload.score_phase;
        game.value.dead_stones = payload.dead_stones;
        game.value.score_acceptance = payload.score_acceptance;
        game.value.provisional_scores = payload.scores;

        if (payload.score_phase === 'marking') {
            stopClock();
        } else if (game.value.status === 'active' && !isGameOver.value) {
            startClock();
        }
    }

    function applyScorePhaseUpdate(data: {
        score_phase?: string;
        dead_stones?: Coordinate[];
        score_acceptance?: { black: boolean; white: boolean };
        scores?: { black: number; white: number } | null;
    }): void {
        if (typeof data.score_phase === 'string') {
            game.value.score_phase = data.score_phase;
        }
        if (Array.isArray(data.dead_stones)) {
            game.value.dead_stones = data.dead_stones;
        }
        if (data.score_acceptance) {
            game.value.score_acceptance = data.score_acceptance;
        }
        if (data.scores) {
            game.value.provisional_scores = data.scores;
        }
    }

    function applyMove(payload: GameMovePayload): void {
        console.log(
            '[WS] applyMove called, payload.move_number:',
            payload.move_number,
            'game.move_count:',
            game.value.move_count
        );

        if (payload.move_number <= game.value.move_count) {
            console.log('[WS] Move already applied, skipping');
            return;
        }

        // Add move to history
        const move = {
            coordinate: payload.coordinate,
            stone: payload.stone,
            captures: payload.captures,
            moveNumber: payload.move_number,
        };

        game.value.move_history = [...game.value.move_history, move];
        game.value.move_count = payload.move_number;
        game.value.current_player = payload.stone === 'black' ? 'white' : 'black';

        // Sync times
        blackTimeRemaining.value = payload.black_time_remaining_ms;
        whiteTimeRemaining.value = payload.white_time_remaining_ms;

        console.log('[WS] Move applied successfully, new move_count:', game.value.move_count);
    }

    function buildMovePayloadFromResponse(data: {
        move?: {
            coordinate: Coordinate | null;
            stone: Stone;
            captures?: Coordinate[];
            moveNumber: number;
        };
        times?: {
            black_time_remaining_ms?: number;
            white_time_remaining_ms?: number;
        };
    }): GameMovePayload | null {
        if (!data.move || typeof data.move.moveNumber !== 'number') return null;

        return {
            coordinate: data.move.coordinate ?? null,
            stone: data.move.stone,
            captures: Array.isArray(data.move.captures) ? data.move.captures : [],
            move_number: data.move.moveNumber,
            black_time_remaining_ms:
                data.times?.black_time_remaining_ms ?? blackTimeRemaining.value,
            white_time_remaining_ms:
                data.times?.white_time_remaining_ms ?? whiteTimeRemaining.value,
        };
    }

    // Handle game ended
    function handleGameEnded(payload: GameEndedPayload): void {
        game.value.status = 'finished';
        game.value.winner = payload.winner;
        game.value.end_reason = payload.end_reason;
        game.value.scores = payload.scores ?? null;

        if (payload.rating_changes) {
            game.value.black_rating_after = payload.rating_changes.black.after;
            game.value.white_rating_after = payload.rating_changes.white.after;
        }

        stopClock();
        options.onGameEnded?.(payload);
    }

    // Handle opponent connection status
    function handleConnectionStatus(payload: PlayerConnectionPayload): void {
        if (payload.player_color !== playerColor.value) {
            opponentConnected.value = payload.connected;
        }
    }

    // Subscribe to WebSocket channels
    function subscribeToGame(): void {
        console.log('[WS] subscribeToGame called');

        if (typeof window !== 'undefined' && (window as unknown as { Echo?: unknown }).Echo) {
            console.log('[WS] Echo exists, attempting to join channel');

            const Echo = (window as any).Echo;

            const channelName = isSpectator.value
                ? `game.${game.value.id}.spectate`
                : `game.${game.value.id}`;

            console.log('[WS] Channel name:', channelName, 'isSpectator:', isSpectator.value);

            if (isSpectator.value) {
                // Spectators use a public channel
                gameChannel = Echo.channel(channelName)
                    .listen('.game.move', (payload: unknown) => {
                        console.log('[WS] Spectator received move:', payload);
                        handleMove(payload as GameMovePayload);
                    })
                    .listen('.game.scoring', (payload: unknown) => {
                        console.log('[WS] Spectator received game.scoring:', payload);
                        handleScoring(payload as GameScoringPayload);
                    })
                    .listen('.game.ended', (payload: unknown) => {
                        console.log('[WS] Spectator received game.ended:', payload);
                        handleGameEnded(payload as GameEndedPayload);
                    });
            } else {
                // Players use a presence channel
                gameChannel = Echo.join(channelName)
                    .here((users: unknown[]) => {
                        console.log('[WS] Joined presence channel, users:', users);
                        opponentConnected.value = users.length > 1;
                    })
                    .joining((user: unknown) => {
                        console.log('[WS] User joining:', user);
                        opponentConnected.value = true;
                    })
                    .leaving((user: unknown) => {
                        console.log('[WS] User leaving:', user);
                        opponentConnected.value = false;
                    })
                    .listen('.game.move', (payload: unknown) => {
                        console.log('[WS] Player received move:', payload);
                        handleMove(payload as GameMovePayload);
                    })
                    .listen('.game.scoring', (payload: unknown) => {
                        console.log('[WS] Player received game.scoring:', payload);
                        handleScoring(payload as GameScoringPayload);
                    })
                    .listen('.game.ended', (payload: unknown) => {
                        console.log('[WS] Player received game.ended:', payload);
                        handleGameEnded(payload as GameEndedPayload);
                    })
                    .listen('.clock.sync', (payload: unknown) => {
                        console.log('[WS] Received clock.sync:', payload);
                        syncClock(payload as ClockSyncPayload);
                    })
                    .listen('.player.connection', (payload: unknown) => {
                        console.log('[WS] Received player.connection:', payload);
                        handleConnectionStatus(payload as PlayerConnectionPayload);
                    })
                    .error((error: unknown) => {
                        console.error('[WS] Channel error:', error);
                    });
            }
        } else {
            console.warn('[WS] Echo not available on window');
        }
    }

    function unsubscribeFromGame(): void {
        if (
            gameChannel &&
            typeof window !== 'undefined' &&
            (window as unknown as { Echo?: unknown }).Echo
        ) {
            const Echo = (window as unknown as { Echo: { leave: (channel: string) => void } }).Echo;
            Echo.leave(`game.${game.value.id}`);
            Echo.leave(`game.${game.value.id}.spectate`);
            gameChannel = null;
        }
    }

    function clearError(): void {
        error.value = null;
    }

    // Watch for game start
    watch(
        [() => game.value.status, () => game.value.score_phase],
        ([status, scorePhase]) => {
            if (status === 'active' && scorePhase !== 'marking' && !isGameOver.value) {
                startClock();
            } else {
                stopClock();
            }
        },
        { immediate: true }
    );

    // Setup on mount
    onMounted(() => {
        subscribeToGame();
        if (game.value.status === 'active' && game.value.score_phase !== 'marking') {
            startClock();
        }
    });

    // Cleanup on unmount
    onUnmounted(() => {
        stopClock();
        unsubscribeFromGame();
    });

    return {
        // State
        game,
        playerColor,
        isSpectator,
        isMyTurn,
        isLoading,
        error,
        board,
        boardSize,
        lastMove,
        isGameOver,
        winner,
        scorePhase,
        isScoring,
        deadStones,
        scoreAcceptance,
        provisionalScores,

        // Time
        blackTimeRemaining,
        whiteTimeRemaining,
        blackTimeFormatted,
        whiteTimeFormatted,

        // Connection
        opponentConnected,

        // Methods
        playMove,
        pass,
        toggleDeadStone,
        acceptScore,
        resign,
        clearError,
    };
}
