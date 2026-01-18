// Go Game TypeScript Interfaces

export type Stone = 'black' | 'white';
export type Intersection = Stone | null;

export interface Coordinate {
    x: number;
    y: number;
}

export interface Move {
    coordinate: Coordinate | null; // null = pass
    stone: Stone;
    captures: Coordinate[];
    moveNumber: number;
}

export type BoardState = Intersection[][];

export interface GameState {
    board: BoardState;
    currentPlayer: Stone;
    moveHistory: Move[];
    blackCaptures: number; // stones captured by black
    whiteCaptures: number; // stones captured by white
    koPoint: Coordinate | null;
    consecutivePasses: number;
    isGameOver: boolean;
    winner: Stone | 'draw' | null;
}

export interface GameConfig {
    boardSize: 9 | 13 | 19;
    komi: number; // compensation for white (usually 6.5)
    handicap: number;
}

export interface Group {
    stones: Coordinate[];
    liberties: Coordinate[];
    color: Stone;
}

export interface Territory {
    black: number;
    white: number;
    dame: number; // neutral points
}

export interface GameScore {
    blackTerritory: number;
    whiteTerritory: number;
    blackCaptures: number;
    whiteCaptures: number;
    komi: number;
    blackTotal: number;
    whiteTotal: number;
    winner: Stone | 'draw';
    margin: number;
}

export interface ReplayPlayer {
    id: number | null;
    name: string;
}

// Saved game record from database (finished games)
export interface SavedGame {
    id: number;
    game_type: 'bot' | 'human';
    board_size: 9 | 13 | 19;
    komi: number;
    winner: Stone | 'draw' | null;
    end_reason: 'score' | 'resignation' | 'timeout' | 'abandonment' | null;
    score_margin: number | null;
    move_count: number;
    black_score: number;
    white_score: number;
    black_captures: number;
    white_captures: number;
    move_history: Move[];
    duration_seconds: number;
    user_won: boolean;
    user_color: Stone | null;
    black_player: ReplayPlayer;
    white_player: ReplayPlayer;
    created_at: string;
}

// Resume game data (for loading in-progress games)
export interface ResumeGame {
    id: number;
    board_size: 9 | 13 | 19;
    komi: number;
    move_count: number;
    black_captures: number;
    white_captures: number;
    move_history: Move[];
    duration_seconds: number;
}

// Ongoing game item for history list view
export interface OngoingGameItem {
    id: number;
    board_size: 9 | 13 | 19;
    move_count: number;
    updated_at: string;
    game_type: 'bot' | 'human';
    status: 'pending' | 'active' | 'finished' | 'abandoned';
    status_label: string;
    opponent: string;
    resume_url: string | null;
    can_delete: boolean;
    delete_url: string | null;
}

// Simplified game item for history list view (finished games)
export interface GameHistoryItem {
    id: number;
    board_size: 9 | 13 | 19;
    winner: Stone | 'draw' | null;
    end_reason: 'score' | 'resignation' | 'timeout' | 'abandonment' | null;
    score_margin: number | null;
    move_count: number;
    user_won: boolean;
    created_at: string;
    game_type: 'bot' | 'human';
    opponent: string;
    detail_url: string;
    can_delete: boolean;
    delete_url: string | null;
}

// Analysis types for training mode
export interface CandidateMove {
    coordinate: Coordinate;
    winRate: number; // 0-100
    rank: number; // 1 = best
    visits?: number;
}

export interface AnalysisResult {
    winRate: {
        black: number; // 0-100
        white: number; // 0-100
    };
    scoreEstimate: {
        lead: number; // positive = black ahead, negative = white ahead
        winner: Stone;
    };
    topMoves: CandidateMove[];
}
