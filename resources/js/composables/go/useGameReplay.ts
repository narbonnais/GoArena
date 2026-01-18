import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

import { createEmptyBoard, setStone, removeStones } from '@/lib/go/board';
import type { BoardState, Move, SavedGame, Stone } from '@/types/go';

export interface UseGameReplayOptions {
    game: SavedGame;
    autoPlaySpeed?: number; // milliseconds between moves
}

export function useGameReplay(options: UseGameReplayOptions) {
    const { game } = options;
    const autoPlaySpeed = ref(options.autoPlaySpeed ?? 1000);

    // Replay state
    const currentMoveIndex = ref(0); // 0 = start (no moves), game.move_history.length = end
    const isPlaying = ref(false);
    let playIntervalId: number | null = null;

    // Computed state at current move
    const board = computed<BoardState>(() => {
        return reconstructBoardAtMove(currentMoveIndex.value);
    });

    const currentMove = computed<Move | null>(() => {
        if (currentMoveIndex.value === 0) return null;
        return game.move_history[currentMoveIndex.value - 1];
    });

    const lastMove = computed<Move | null>(() => {
        return currentMove.value;
    });

    const currentPlayer = computed<Stone>(() => {
        // After move N, it's the opponent's turn
        return currentMoveIndex.value % 2 === 0 ? 'black' : 'white';
    });

    const blackCaptures = computed(() => {
        let captures = 0;
        const history = game.move_history ?? [];
        for (let i = 0; i < currentMoveIndex.value && i < history.length; i++) {
            const move = history[i];
            if (!move) continue;
            if (move.stone === 'black' && Array.isArray(move.captures)) {
                captures += move.captures.length;
            }
        }
        return captures;
    });

    const whiteCaptures = computed(() => {
        let captures = 0;
        const history = game.move_history ?? [];
        for (let i = 0; i < currentMoveIndex.value && i < history.length; i++) {
            const move = history[i];
            if (!move) continue;
            if (move.stone === 'white' && Array.isArray(move.captures)) {
                captures += move.captures.length;
            }
        }
        return captures;
    });

    const isAtStart = computed(() => currentMoveIndex.value === 0);
    const isAtEnd = computed(() => currentMoveIndex.value === (game.move_history?.length ?? 0));
    const totalMoves = computed(() => game.move_history?.length ?? 0);
    const progress = computed(() => {
        if (totalMoves.value === 0) return 0;
        return (currentMoveIndex.value / totalMoves.value) * 100;
    });

    // Reconstruct board state up to a given move index
    function reconstructBoardAtMove(moveIndex: number): BoardState {
        let boardState = createEmptyBoard(game.board_size);
        const history = game.move_history ?? [];

        for (let i = 0; i < moveIndex && i < history.length; i++) {
            const move = history[i];
            if (!move) continue;

            // Apply captures first (remove captured stones)
            if (Array.isArray(move.captures) && move.captures.length > 0) {
                boardState = removeStones(boardState, move.captures);
            }

            // Place the stone (if not a pass)
            if (move.coordinate !== null && move.coordinate !== undefined) {
                boardState = setStone(boardState, move.coordinate, move.stone);
            }
        }

        return boardState;
    }

    // Navigation functions
    function goToStart() {
        stopAutoPlay();
        currentMoveIndex.value = 0;
    }

    function goToEnd() {
        stopAutoPlay();
        currentMoveIndex.value = game.move_history?.length ?? 0;
    }

    function nextMove() {
        const historyLength = game.move_history?.length ?? 0;
        if (currentMoveIndex.value < historyLength) {
            currentMoveIndex.value++;
        } else {
            // At end, stop auto-play
            stopAutoPlay();
        }
    }

    function prevMove() {
        stopAutoPlay();
        if (currentMoveIndex.value > 0) {
            currentMoveIndex.value--;
        }
    }

    function goToMove(index: number) {
        stopAutoPlay();
        const historyLength = game.move_history?.length ?? 0;
        currentMoveIndex.value = Math.max(0, Math.min(index, historyLength));
    }

    // Auto-play functions
    function startAutoPlay() {
        if (isPlaying.value) return;
        if (isAtEnd.value) {
            // If at end, start from beginning
            currentMoveIndex.value = 0;
        }
        isPlaying.value = true;
        playIntervalId = window.setInterval(() => {
            const historyLength = game.move_history?.length ?? 0;
            if (currentMoveIndex.value < historyLength) {
                currentMoveIndex.value++;
            } else {
                stopAutoPlay();
            }
        }, autoPlaySpeed.value);
    }

    function stopAutoPlay() {
        if (playIntervalId !== null) {
            clearInterval(playIntervalId);
            playIntervalId = null;
        }
        isPlaying.value = false;
    }

    function toggleAutoPlay() {
        if (isPlaying.value) {
            stopAutoPlay();
        } else {
            startAutoPlay();
        }
    }

    function setAutoPlaySpeed(speed: number) {
        autoPlaySpeed.value = speed;
        if (isPlaying.value) {
            // Restart with new speed
            stopAutoPlay();
            startAutoPlay();
        }
    }

    // Keyboard controls
    function handleKeydown(event: KeyboardEvent) {
        switch (event.key) {
            case 'ArrowLeft':
                event.preventDefault();
                prevMove();
                break;
            case 'ArrowRight':
                event.preventDefault();
                nextMove();
                break;
            case 'Home':
                event.preventDefault();
                goToStart();
                break;
            case 'End':
                event.preventDefault();
                goToEnd();
                break;
            case ' ':
                event.preventDefault();
                toggleAutoPlay();
                break;
        }
    }

    // Setup and cleanup
    onMounted(() => {
        window.addEventListener('keydown', handleKeydown);
    });

    onUnmounted(() => {
        stopAutoPlay();
        window.removeEventListener('keydown', handleKeydown);
    });

    return {
        // State
        currentMoveIndex,
        isPlaying,
        autoPlaySpeed,

        // Computed
        board,
        currentMove,
        lastMove,
        currentPlayer,
        blackCaptures,
        whiteCaptures,
        isAtStart,
        isAtEnd,
        totalMoves,
        progress,

        // Navigation
        goToStart,
        goToEnd,
        nextMove,
        prevMove,
        goToMove,

        // Auto-play
        startAutoPlay,
        stopAutoPlay,
        toggleAutoPlay,
        setAutoPlaySpeed,
    };
}
