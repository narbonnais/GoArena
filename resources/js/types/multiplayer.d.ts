// Multiplayer Go Types

import type { Coordinate, Move, Stone } from './go';

export interface UserRating {
    id: number;
    user_id: number;
    board_size: 9 | 13 | 19;
    rating: number;
    games_played: number;
    wins: number;
    losses: number;
    draws: number;
    peak_rating: number;
    rank_title: string;
    win_rate: number;
}

export interface TimeControl {
    id: number;
    name: string;
    slug: string;
    display_time: string;
    initial_time_seconds: number;
    increment_seconds: number;
}

export interface PlayerInfo {
    id: number;
    name: string;
    display_name: string | null;
    avatar_url: string | null;
    country_code: string | null;
    rating?: number;
    rank_title?: string;
    is_online?: boolean;
}

export type GameStatus = 'pending' | 'active' | 'finished' | 'abandoned';
export type ScorePhase = 'none' | 'marking';
export type EndReason = 'score' | 'resignation' | 'timeout' | 'abandonment';

export interface MultiplayerGame {
    id: number;
    black_player: PlayerInfo;
    white_player: PlayerInfo;
    board_size: 9 | 13 | 19;
    time_control: TimeControl;
    komi: number;
    is_ranked: boolean;
    status: GameStatus;
    score_phase: ScorePhase;
    current_player: Stone;
    black_time_remaining_ms: number;
    white_time_remaining_ms: number;
    last_move_at: string | null;
    winner: Stone | 'draw' | null;
    end_reason: EndReason | null;
    move_history: Move[];
    move_count: number;
    captures: {
        black: number;
        white: number;
    };
    dead_stones: Coordinate[];
    score_acceptance: {
        black: boolean;
        white: boolean;
    };
    provisional_scores: {
        black: number;
        white: number;
    } | null;
    scores: {
        black: number;
        white: number;
    } | null;
    black_rating_before: number | null;
    black_rating_after: number | null;
    white_rating_before: number | null;
    white_rating_after: number | null;
    created_at: string;
}

export interface MatchmakingStatus {
    in_queue: boolean;
    board_size?: 9 | 13 | 19;
    time_control?: TimeControl;
    rating?: number;
    max_rating_diff?: number;
    is_ranked?: boolean;
    wait_time_seconds?: number;
    joined_at?: string;
    expires_at?: string;
}

export interface MatchFoundPayload {
    game_id: number;
    opponent: {
        id: number;
        name: string;
        rating: number | null;
    };
    your_color: Stone;
}

export interface QueueEntry {
    board_size: 9 | 13 | 19;
    time_control: TimeControl;
    rating: number;
    max_rating_diff: number;
    is_ranked: boolean;
    joined_at: string;
    expires_at: string;
}

export interface GameMovePayload {
    coordinate: Coordinate | null;
    stone: Stone;
    move_number: number;
    black_time_remaining_ms: number;
    white_time_remaining_ms: number;
    captures: Coordinate[];
}

export interface GameEndedPayload {
    winner: Stone | 'draw';
    end_reason: EndReason;
    scores?: {
        black: number;
        white: number;
    };
    rating_changes?: {
        black: { before: number; after: number };
        white: { before: number; after: number };
    };
}

export interface GameScoringPayload {
    score_phase: ScorePhase;
    dead_stones: Coordinate[];
    score_acceptance: {
        black: boolean;
        white: boolean;
    };
    scores: {
        black: number;
        white: number;
    };
}

export interface ClockSyncPayload {
    black_time_remaining_ms: number;
    white_time_remaining_ms: number;
    current_player: Stone;
    server_time: string;
}

export interface PlayerConnectionPayload {
    user_id: number;
    player_color: Stone;
    connected: boolean;
}

export interface LeaderboardEntry {
    rank: number;
    user: PlayerInfo;
    rating: number;
    rank_title: string;
    games_played: number;
    wins: number;
    losses: number;
    win_rate: number;
}

export interface LiveGameItem {
    id: number;
    black_player: PlayerInfo;
    white_player: PlayerInfo;
    board_size: 9 | 13 | 19;
    time_control: TimeControl;
    move_count: number;
    current_player: Stone;
    black_time_remaining_ms: number;
    white_time_remaining_ms: number;
    spectator_count?: number;
}
