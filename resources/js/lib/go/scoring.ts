import type { BoardState, Coordinate, GameScore, GameState, Stone, Territory } from '@/types/go';

import { coordKey, getAdjacentCoordinates, getStone } from './board';

/**
 * Find a territory region starting from an empty point using flood-fill.
 * Returns the empty points in the region and which colors border it.
 */
function floodFillTerritory(
    board: BoardState,
    start: Coordinate,
    visited: Set<string>,
): { points: Coordinate[]; borders: Set<Stone> } {
    const points: Coordinate[] = [];
    const borders = new Set<Stone>();
    const stack: Coordinate[] = [start];

    while (stack.length > 0) {
        const current = stack.pop()!;
        const key = coordKey(current);

        if (visited.has(key)) continue;

        const stone = getStone(board, current);

        if (stone === null) {
            // Empty point, add to territory
            visited.add(key);
            points.push(current);

            // Explore neighbors
            for (const adj of getAdjacentCoordinates(board, current)) {
                const adjKey = coordKey(adj);
                if (!visited.has(adjKey)) {
                    stack.push(adj);
                }
            }
        } else {
            // Stone found, it's a border
            borders.add(stone);
        }
    }

    return { points, borders };
}

/**
 * Calculate territory for both players.
 * A region is territory if it's only bordered by one color.
 */
export function calculateTerritory(board: BoardState): Territory {
    const size = board.length;
    const visited = new Set<string>();
    let blackTerritory = 0;
    let whiteTerritory = 0;
    let dame = 0;

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const coord = { x, y };
            const key = coordKey(coord);

            if (visited.has(key)) continue;

            const stone = getStone(board, coord);
            if (stone !== null) {
                visited.add(key);
                continue;
            }

            // Found an empty point, flood fill to find the territory
            const { points, borders } = floodFillTerritory(board, coord, visited);

            if (borders.size === 1) {
                // Territory belongs to the single bordering color
                const owner = borders.values().next().value as Stone;
                if (owner === 'black') {
                    blackTerritory += points.length;
                } else {
                    whiteTerritory += points.length;
                }
            } else if (borders.size === 2) {
                // Dame (neutral) - bordered by both colors
                dame += points.length;
            }
            // If borders.size === 0, it's an isolated empty region (shouldn't happen in a real game)
        }
    }

    return { black: blackTerritory, white: whiteTerritory, dame };
}

/**
 * Count stones on the board for each color
 */
export function countStones(board: BoardState): { black: number; white: number } {
    const size = board.length;
    let black = 0;
    let white = 0;

    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const stone = board[y][x];
            if (stone === 'black') black++;
            else if (stone === 'white') white++;
        }
    }

    return { black, white };
}

/**
 * Calculate final score using Japanese territory scoring.
 * Score = Territory + Captures + Komi.
 */
export function calculateScore(state: GameState, komi: number): GameScore {
    const territory = calculateTerritory(state.board);

    const blackTotal = territory.black + state.blackCaptures;
    const whiteTotal = territory.white + state.whiteCaptures + komi;

    let winner: Stone | 'draw';
    let margin: number;

    if (blackTotal > whiteTotal) {
        winner = 'black';
        margin = blackTotal - whiteTotal;
    } else if (whiteTotal > blackTotal) {
        winner = 'white';
        margin = whiteTotal - blackTotal;
    } else {
        winner = 'draw';
        margin = 0;
    }

    return {
        blackTerritory: territory.black,
        whiteTerritory: territory.white,
        blackCaptures: state.blackCaptures,
        whiteCaptures: state.whiteCaptures,
        komi,
        blackTotal,
        whiteTotal,
        winner,
        margin,
    };
}

/**
 * Simple heuristic score for AI evaluation (fast approximation)
 * Returns positive for black advantage, negative for white advantage
 */
export function evaluatePosition(board: BoardState): number {
    const stones = countStones(board);
    const territory = calculateTerritory(board);

    // Simple evaluation: stones + territory
    const blackScore = stones.black + territory.black;
    const whiteScore = stones.white + territory.white;

    return blackScore - whiteScore;
}

/**
 * Quick board evaluation for MCTS playouts (very fast)
 * Just counts stones, ignores territory for speed
 */
export function quickEvaluate(board: BoardState): number {
    const stones = countStones(board);
    return stones.black - stones.white;
}
