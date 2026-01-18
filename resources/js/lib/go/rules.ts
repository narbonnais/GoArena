import type { BoardState, Coordinate, Group, Stone } from '@/types/go';

import {
    cloneBoard,
    coordEquals,
    coordKey,
    getAdjacentCoordinates,
    getOpponent,
    getStone,
    hashBoard,
    isEmpty,
    removeStones,
    setStone,
} from './board';

/**
 * Find all stones connected to the given coordinate (flood-fill)
 */
export function findGroup(board: BoardState, coord: Coordinate): Group | null {
    const stone = getStone(board, coord);
    if (stone === null) return null;

    const visited = new Set<string>();
    const stones: Coordinate[] = [];
    const liberties: Coordinate[] = [];
    const libertiesSet = new Set<string>();

    const stack: Coordinate[] = [coord];

    while (stack.length > 0) {
        const current = stack.pop()!;
        const key = coordKey(current);

        if (visited.has(key)) continue;
        visited.add(key);

        const currentStone = getStone(board, current);
        if (currentStone === stone) {
            stones.push(current);

            // Check adjacent positions
            for (const adj of getAdjacentCoordinates(board, current)) {
                const adjKey = coordKey(adj);
                const adjStone = getStone(board, adj);

                if (adjStone === stone && !visited.has(adjKey)) {
                    stack.push(adj);
                } else if (adjStone === null && !libertiesSet.has(adjKey)) {
                    liberties.push(adj);
                    libertiesSet.add(adjKey);
                }
            }
        }
    }

    return { stones, liberties, color: stone };
}

/**
 * Count liberties of a group
 */
export function countLiberties(board: BoardState, coord: Coordinate): number {
    const group = findGroup(board, coord);
    return group ? group.liberties.length : 0;
}

/**
 * Check if a group would be captured (has no liberties)
 */
export function isCaptured(board: BoardState, coord: Coordinate): boolean {
    return countLiberties(board, coord) === 0;
}

/**
 * Find all groups of a given color that would be captured
 */
export function findCapturedGroups(board: BoardState, color: Stone): Group[] {
    const visited = new Set<string>();
    const capturedGroups: Group[] = [];

    const size = board.length;
    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const coord = { x, y };
            const key = coordKey(coord);

            if (visited.has(key)) continue;

            const stone = getStone(board, coord);
            if (stone !== color) continue;

            const group = findGroup(board, coord);
            if (group) {
                // Mark all stones in this group as visited
                for (const s of group.stones) {
                    visited.add(coordKey(s));
                }

                if (group.liberties.length === 0) {
                    capturedGroups.push(group);
                }
            }
        }
    }

    return capturedGroups;
}

/**
 * Result of attempting to place a stone
 */
export interface MoveResult {
    valid: boolean;
    board: BoardState;
    captures: Coordinate[];
    error?: string;
}

/**
 * Attempt to place a stone and resolve captures
 * Returns the resulting board state and captured stones
 */
export function placeStone(
    board: BoardState,
    coord: Coordinate,
    stone: Stone,
    koPoint: Coordinate | null = null,
): MoveResult {
    // Check if position is empty
    if (!isEmpty(board, coord)) {
        return { valid: false, board, captures: [], error: 'Position is occupied' };
    }

    // Check ko rule
    if (koPoint && coordEquals(coord, koPoint)) {
        return { valid: false, board, captures: [], error: 'Ko rule violation' };
    }

    // Place the stone
    let newBoard = setStone(board, coord, stone);

    // Find and remove captured opponent stones
    const opponent = getOpponent(stone);
    const capturedGroups = findCapturedGroups(newBoard, opponent);
    const captures: Coordinate[] = [];

    for (const group of capturedGroups) {
        captures.push(...group.stones);
    }

    if (captures.length > 0) {
        newBoard = removeStones(newBoard, captures);
    }

    // Check for suicide (self-capture without capturing anything)
    const selfGroup = findGroup(newBoard, coord);
    if (selfGroup && selfGroup.liberties.length === 0 && captures.length === 0) {
        return { valid: false, board, captures: [], error: 'Suicide is not allowed' };
    }

    return { valid: true, board: newBoard, captures };
}

/**
 * Determine if a move creates a ko situation
 * Ko occurs when exactly one stone is captured and playing at that position
 * would immediately recapture the single stone just played
 */
export function detectKo(board: BoardState, coord: Coordinate, captures: Coordinate[]): Coordinate | null {
    // Ko only occurs when exactly one stone is captured
    if (captures.length !== 1) return null;

    const capturedCoord = captures[0];

    // Check if the captured position only has the new stone as neighbor
    const newBoard = board;
    const neighbors = getAdjacentCoordinates(newBoard, capturedCoord);
    const stoneNeighbors = neighbors.filter((n) => getStone(newBoard, n) !== null);

    // If the captured stone had only one neighbor (the stone that captured it)
    // and that stone has only one liberty (the captured position), it's ko
    if (stoneNeighbors.length === 1 && coordEquals(stoneNeighbors[0], coord)) {
        const capturingGroup = findGroup(newBoard, coord);
        if (capturingGroup && capturingGroup.liberties.length === 1) {
            return capturedCoord;
        }
    }

    return null;
}

/**
 * Check if a move is legal
 */
export function isLegalMove(
    board: BoardState,
    coord: Coordinate,
    stone: Stone,
    koPoint: Coordinate | null = null,
): boolean {
    const result = placeStone(board, coord, stone, koPoint);
    return result.valid;
}

/**
 * Get all legal moves for a player
 */
export function getLegalMoves(board: BoardState, stone: Stone, koPoint: Coordinate | null = null): Coordinate[] {
    const legalMoves: Coordinate[] = [];
    const size = board.length;

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const coord = { x, y };
            if (isLegalMove(board, coord, stone, koPoint)) {
                legalMoves.push(coord);
            }
        }
    }

    return legalMoves;
}

/**
 * Check if a position is in atari (has exactly one liberty)
 */
export function isInAtari(board: BoardState, coord: Coordinate): boolean {
    return countLiberties(board, coord) === 1;
}

/**
 * Find all groups in atari for a given color
 */
export function findGroupsInAtari(board: BoardState, color: Stone): Group[] {
    const visited = new Set<string>();
    const atariGroups: Group[] = [];

    const size = board.length;
    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const coord = { x, y };
            const key = coordKey(coord);

            if (visited.has(key)) continue;

            const stone = getStone(board, coord);
            if (stone !== color) continue;

            const group = findGroup(board, coord);
            if (group) {
                for (const s of group.stones) {
                    visited.add(coordKey(s));
                }

                if (group.liberties.length === 1) {
                    atariGroups.push(group);
                }
            }
        }
    }

    return atariGroups;
}

/**
 * Create a position hash for superko detection (optional, not implemented in basic rules)
 */
export function getPositionHash(board: BoardState, currentPlayer: Stone): string {
    return `${currentPlayer}:${hashBoard(board)}`;
}
