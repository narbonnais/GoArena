import { computed, ref, shallowRef } from 'vue';

import {
    createInitialState,
    DEFAULT_CONFIG,
    getLastMove,
    pass as gamePass,
    playMove,
    resign as gameResign,
} from '@/lib/go/game';
import { calculateScore } from '@/lib/go/scoring';
import type { Coordinate, GameConfig, GameState, GameScore, Move, Stone, ResumeGame } from '@/types/go';

export interface UseGoGameOptions {
    boardSize?: 9 | 13 | 19;
    komi?: number;
    resumeGame?: ResumeGame;
}

/**
 * Recreate game state by replaying all moves from history
 */
function createStateFromMoveHistory(moveHistory: Move[], config: GameConfig): GameState {
    let state = createInitialState(config);

    for (const move of moveHistory) {
        if (move.coordinate === null) {
            // It's a pass
            const result = gamePass(state);
            if (result.success) {
                state = result.state;
            }
        } else {
            // It's a stone placement
            const result = playMove(state, move.coordinate);
            if (result.success) {
                state = result.state;
            }
        }
    }

    return state;
}

export function useGoGame(options: UseGoGameOptions = {}) {
    const config = ref<GameConfig>({
        ...DEFAULT_CONFIG,
        boardSize: options.boardSize ?? options.resumeGame?.board_size ?? 9,
        komi: options.komi ?? options.resumeGame?.komi ?? 6.5,
    });

    // If resuming a game, recreate state from move history
    const initialState = options.resumeGame
        ? createStateFromMoveHistory(options.resumeGame.move_history, config.value)
        : createInitialState(config.value);

    const state = shallowRef<GameState>(initialState);

    // Computed properties
    const board = computed(() => state.value.board);
    const currentPlayer = computed(() => state.value.currentPlayer);
    const isGameOver = computed(() => state.value.isGameOver);
    const winner = computed(() => state.value.winner);
    const blackCaptures = computed(() => state.value.blackCaptures);
    const whiteCaptures = computed(() => state.value.whiteCaptures);
    const moveCount = computed(() => state.value.moveHistory.length);

    const lastMove = computed<Move | null>(() => {
        return getLastMove(state.value);
    });

    const score = computed<GameScore | null>(() => {
        if (!state.value.isGameOver) return null;
        return calculateScore(state.value, config.value.komi);
    });

    // Actions
    function play(coord: Coordinate): boolean {
        const result = playMove(state.value, coord);
        if (result.success) {
            state.value = result.state;
            return true;
        }
        return false;
    }

    function pass(): boolean {
        const result = gamePass(state.value);
        if (result.success) {
            state.value = result.state;
            return true;
        }
        return false;
    }

    function resign(): void {
        state.value = gameResign(state.value);
    }

    function reset(newConfig?: Partial<GameConfig>): void {
        if (newConfig) {
            config.value = { ...config.value, ...newConfig };
        }
        state.value = createInitialState(config.value);
    }

    function isValidMove(coord: Coordinate): boolean {
        const result = playMove(state.value, coord);
        return result.success;
    }

    /**
     * Undo the last move pair (player move + AI response)
     * Returns true if undo was successful
     */
    function undo(): boolean {
        const history = state.value.moveHistory;

        // Need at least 2 moves to undo (player + AI)
        if (history.length < 2) return false;

        // Don't allow undo if game is over
        if (state.value.isGameOver) return false;

        // Remove last 2 moves and recreate state
        const newHistory = history.slice(0, -2);
        state.value = createStateFromMoveHistory(newHistory, config.value);

        return true;
    }

    const canUndo = computed(() => {
        return state.value.moveHistory.length >= 2 && !state.value.isGameOver;
    });

    return {
        // State
        config,
        state,

        // Computed
        board,
        currentPlayer,
        isGameOver,
        winner,
        blackCaptures,
        whiteCaptures,
        moveCount,
        lastMove,
        score,

        // Actions
        play,
        pass,
        resign,
        reset,
        isValidMove,
        undo,
        canUndo,
    };
}
