import type { BoardState, Coordinate, Intersection, Stone } from '@/types/go';

/**
 * Create an empty board of the specified size
 */
export function createEmptyBoard(size: number): BoardState {
    return Array.from({ length: size }, () => Array.from({ length: size }, () => null));
}

/**
 * Clone a board state (deep copy)
 */
export function cloneBoard(board: BoardState): BoardState {
    return board.map((row) => [...row]);
}

/**
 * Get the stone at a coordinate
 */
export function getStone(board: BoardState, coord: Coordinate): Intersection {
    if (!isValidCoordinate(board, coord)) return null;
    return board[coord.y][coord.x];
}

/**
 * Set a stone at a coordinate (returns new board)
 */
export function setStone(board: BoardState, coord: Coordinate, stone: Intersection): BoardState {
    const newBoard = cloneBoard(board);
    newBoard[coord.y][coord.x] = stone;
    return newBoard;
}

/**
 * Remove a stone from the board (returns new board)
 */
export function removeStone(board: BoardState, coord: Coordinate): BoardState {
    return setStone(board, coord, null);
}

/**
 * Remove multiple stones from the board (returns new board)
 */
export function removeStones(board: BoardState, coords: Coordinate[]): BoardState {
    const newBoard = cloneBoard(board);
    for (const coord of coords) {
        newBoard[coord.y][coord.x] = null;
    }
    return newBoard;
}

/**
 * Check if a coordinate is valid on the board
 */
export function isValidCoordinate(board: BoardState, coord: Coordinate): boolean {
    const size = board.length;
    return coord.x >= 0 && coord.x < size && coord.y >= 0 && coord.y < size;
}

/**
 * Check if a coordinate is empty
 */
export function isEmpty(board: BoardState, coord: Coordinate): boolean {
    return isValidCoordinate(board, coord) && getStone(board, coord) === null;
}

/**
 * Get all adjacent coordinates (up, down, left, right)
 */
export function getAdjacentCoordinates(board: BoardState, coord: Coordinate): Coordinate[] {
    const adjacent: Coordinate[] = [
        { x: coord.x - 1, y: coord.y },
        { x: coord.x + 1, y: coord.y },
        { x: coord.x, y: coord.y - 1 },
        { x: coord.x, y: coord.y + 1 },
    ];
    return adjacent.filter((c) => isValidCoordinate(board, c));
}

/**
 * Get the opponent's color
 */
export function getOpponent(stone: Stone): Stone {
    return stone === 'black' ? 'white' : 'black';
}

/**
 * Get board size
 */
export function getBoardSize(board: BoardState): number {
    return board.length;
}

/**
 * Compare two coordinates for equality
 */
export function coordEquals(a: Coordinate, b: Coordinate): boolean {
    return a.x === b.x && a.y === b.y;
}

/**
 * Check if a coordinate exists in a list
 */
export function coordInList(coord: Coordinate, list: Coordinate[]): boolean {
    return list.some((c) => coordEquals(c, coord));
}

/**
 * Create a unique key for a coordinate (for Set/Map usage)
 */
export function coordKey(coord: Coordinate): string {
    return `${coord.x},${coord.y}`;
}

/**
 * Parse a coordinate key back to Coordinate
 */
export function parseCoordKey(key: string): Coordinate {
    const [x, y] = key.split(',').map(Number);
    return { x, y };
}

/**
 * Get all empty intersections on the board
 */
export function getEmptyIntersections(board: BoardState): Coordinate[] {
    const empty: Coordinate[] = [];
    const size = board.length;
    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            if (board[y][x] === null) {
                empty.push({ x, y });
            }
        }
    }
    return empty;
}

/**
 * Create a hash of the board state (for ko detection)
 */
export function hashBoard(board: BoardState): string {
    return board.map((row) => row.map((cell) => (cell === null ? '.' : cell === 'black' ? 'B' : 'W')).join('')).join('|');
}
