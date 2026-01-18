import type { BoardState, Coordinate, GameConfig, GameState, Move, Stone } from '@/types/go';

import { cloneBoard, createEmptyBoard, getOpponent } from './board';
import { detectKo, getLegalMoves, placeStone } from './rules';
import { calculateScore } from './scoring';

/**
 * Default game configuration
 */
export const DEFAULT_CONFIG: GameConfig = {
    boardSize: 9,
    komi: 6.5,
    handicap: 0,
};

/**
 * Create initial game state
 */
export function createInitialState(config: GameConfig = DEFAULT_CONFIG): GameState {
    return {
        board: createEmptyBoard(config.boardSize),
        currentPlayer: 'black',
        moveHistory: [],
        blackCaptures: 0,
        whiteCaptures: 0,
        koPoint: null,
        consecutivePasses: 0,
        isGameOver: false,
        winner: null,
    };
}

/**
 * Clone a game state (deep copy)
 */
export function cloneGameState(state: GameState): GameState {
    return {
        board: cloneBoard(state.board),
        currentPlayer: state.currentPlayer,
        moveHistory: state.moveHistory.map((m) => ({
            ...m,
            captures: [...m.captures],
        })),
        blackCaptures: state.blackCaptures,
        whiteCaptures: state.whiteCaptures,
        koPoint: state.koPoint ? { ...state.koPoint } : null,
        consecutivePasses: state.consecutivePasses,
        isGameOver: state.isGameOver,
        winner: state.winner,
    };
}

/**
 * Result of making a move
 */
export interface PlayResult {
    success: boolean;
    state: GameState;
    error?: string;
}

/**
 * Play a move at the specified coordinate
 */
export function playMove(state: GameState, coord: Coordinate): PlayResult {
    if (state.isGameOver) {
        return { success: false, state, error: 'Game is over' };
    }

    const result = placeStone(state.board, coord, state.currentPlayer, state.koPoint);

    if (!result.valid) {
        return { success: false, state, error: result.error };
    }

    // Create new state
    const newState = cloneGameState(state);
    newState.board = result.board;

    // Record the move
    const move: Move = {
        coordinate: coord,
        stone: state.currentPlayer,
        captures: result.captures,
        moveNumber: state.moveHistory.length + 1,
    };
    newState.moveHistory.push(move);

    // Update captures
    if (state.currentPlayer === 'black') {
        newState.blackCaptures += result.captures.length;
    } else {
        newState.whiteCaptures += result.captures.length;
    }

    // Check for ko
    newState.koPoint = detectKo(newState.board, coord, result.captures);

    // Reset consecutive passes
    newState.consecutivePasses = 0;

    // Switch player
    newState.currentPlayer = getOpponent(state.currentPlayer);

    return { success: true, state: newState };
}

/**
 * Pass (skip turn)
 */
export function pass(state: GameState): PlayResult {
    if (state.isGameOver) {
        return { success: false, state, error: 'Game is over' };
    }

    const newState = cloneGameState(state);

    // Record the pass
    const move: Move = {
        coordinate: null,
        stone: state.currentPlayer,
        captures: [],
        moveNumber: state.moveHistory.length + 1,
    };
    newState.moveHistory.push(move);

    // Clear ko point on pass
    newState.koPoint = null;

    // Increment consecutive passes
    newState.consecutivePasses += 1;

    // Check for game end (two consecutive passes)
    if (newState.consecutivePasses >= 2) {
        newState.isGameOver = true;
    }

    // Switch player
    newState.currentPlayer = getOpponent(state.currentPlayer);

    return { success: true, state: newState };
}

/**
 * Resign the game
 */
export function resign(state: GameState): GameState {
    const newState = cloneGameState(state);
    newState.isGameOver = true;
    newState.winner = getOpponent(state.currentPlayer);
    return newState;
}

/**
 * Get all legal moves for current player
 */
export function getAvailableMoves(state: GameState): Coordinate[] {
    if (state.isGameOver) return [];
    return getLegalMoves(state.board, state.currentPlayer, state.koPoint);
}

/**
 * Check if a move is valid
 */
export function isValidMove(state: GameState, coord: Coordinate): boolean {
    if (state.isGameOver) return false;
    const result = placeStone(state.board, coord, state.currentPlayer, state.koPoint);
    return result.valid;
}

/**
 * Get the last move played
 */
export function getLastMove(state: GameState): Move | null {
    if (state.moveHistory.length === 0) return null;
    return state.moveHistory[state.moveHistory.length - 1];
}

/**
 * Undo the last move (returns new state)
 */
export function undoMove(state: GameState, config: GameConfig): GameState | null {
    if (state.moveHistory.length === 0) return null;

    // Replay all moves except the last one
    let newState = createInitialState(config);
    const movesToReplay = state.moveHistory.slice(0, -1);

    for (const move of movesToReplay) {
        if (move.coordinate === null) {
            const result = pass(newState);
            newState = result.state;
        } else {
            const result = playMove(newState, move.coordinate);
            if (!result.success) {
                console.error('Failed to replay move during undo:', move);
                return null;
            }
            newState = result.state;
        }
    }

    return newState;
}

/**
 * Game class for convenience (wraps the functional approach)
 */
export class Game {
    private state: GameState;
    private config: GameConfig;

    constructor(config: Partial<GameConfig> = {}) {
        this.config = { ...DEFAULT_CONFIG, ...config };
        this.state = createInitialState(this.config);
    }

    getState(): GameState {
        return this.state;
    }

    getConfig(): GameConfig {
        return this.config;
    }

    play(coord: Coordinate): PlayResult {
        const result = playMove(this.state, coord);
        if (result.success) {
            this.state = result.state;
        }
        return result;
    }

    pass(): PlayResult {
        const result = pass(this.state);
        if (result.success) {
            this.state = result.state;
        }
        return result;
    }

    resign(): void {
        this.state = resign(this.state);
    }

    undo(): boolean {
        const newState = undoMove(this.state, this.config);
        if (newState) {
            this.state = newState;
            return true;
        }
        return false;
    }

    isGameOver(): boolean {
        return this.state.isGameOver;
    }

    getCurrentPlayer(): Stone {
        return this.state.currentPlayer;
    }

    getAvailableMoves(): Coordinate[] {
        return getAvailableMoves(this.state);
    }

    isValidMove(coord: Coordinate): boolean {
        return isValidMove(this.state, coord);
    }

    getLastMove(): Move | null {
        return getLastMove(this.state);
    }

    reset(): void {
        this.state = createInitialState(this.config);
    }
}
