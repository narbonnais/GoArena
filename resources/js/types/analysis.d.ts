// Analysis Explorer TypeScript Interfaces

import type { Coordinate, Stone } from './go';

// Move annotation symbols
export type MoveSymbol = 'good' | 'bad' | 'blunder';

// Node in the move tree
export interface MoveNode {
    id: string;
    coordinate: Coordinate | null;  // null = pass
    stone: Stone;
    captures: Coordinate[];
    moveNumber: number;

    // Tree structure
    parent: string | null;          // Parent node ID
    children: string[];             // IDs of child variations

    // Annotations
    comment: string | null;
    symbols: MoveSymbol[];

    // Optional KataGo analysis
    analysis?: {
        winRate: number;
        scoreEstimate: number;
    };
}

// Complete move tree structure
export interface MoveTree {
    nodes: Record<string, MoveNode>;
    rootId: string;
    currentNodeId: string;
}

// Serialized version for database storage (same structure, but for clarity)
export type SerializedMoveTree = MoveTree;

// Saved analysis study
export interface AnalysisStudy {
    id: number;
    user_id: number;
    title: string;
    description: string | null;
    board_size: 9 | 13 | 19;
    komi: number;
    move_tree: MoveTree;
    source_game_id: number | null;  // Link to original game if created from game
    is_public: boolean;
    created_at: string;
    updated_at: string;
}

// Form data for creating/updating studies
export interface AnalysisStudyFormData {
    title: string;
    description?: string | null;
    board_size: 9 | 13 | 19;
    komi: number;
    move_tree: MoveTree;
    source_game_id?: number | null;
    is_public: boolean;
}

// Study list item (simplified for list views)
export interface AnalysisStudyListItem {
    id: number;
    title: string;
    description: string | null;
    board_size: 9 | 13 | 19;
    move_count: number;
    is_public: boolean;
    created_at: string;
    updated_at: string;
}
