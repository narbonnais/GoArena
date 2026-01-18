// Learn module TypeScript interfaces
import type { Coordinate, Stone } from './go';

export type LessonCategory = 'basics' | 'capturing' | 'territory' | 'tactics' | 'strategy';
export type LessonDifficulty = 'beginner' | 'intermediate' | 'advanced';
export type LessonStepType = 'instruction' | 'placement' | 'puzzle' | 'demonstration';

export interface LessonHighlight {
    coordinates: Coordinate[];
    type: 'circle' | 'square';
    color?: string;
}

export interface SequenceMove {
    coordinate: Coordinate;
    stone: Stone;
    delay?: number;
}

export interface LessonStep {
    id: string;
    type: LessonStepType;
    title?: string;
    instruction: string;
    hint?: string;
    boardSize: 9 | 13 | 19;
    initialBlack: Coordinate[];
    initialWhite: Coordinate[];
    correctMoves?: Coordinate[];
    wrongMoveResponse?: string;
    highlights?: LessonHighlight[];
    sequence?: SequenceMove[];
    playerColor?: Stone;
}

export interface Lesson {
    id: number;
    slug: string;
    title: string;
    description: string;
    category: LessonCategory;
    difficulty: LessonDifficulty;
    duration: string;
    order: number;
    prerequisites: number[];
    steps: LessonStep[];
}

export interface LessonProgress {
    lesson_id: number;
    completed: boolean;
    current_step: number;
    started_at: string | null;
    completed_at: string | null;
}

export interface LessonWithProgress extends Lesson {
    progress: LessonProgress | null;
    locked: boolean;
}

// For the learn page list view
export interface LessonListItem {
    id: number;
    slug: string;
    title: string;
    description: string;
    category: LessonCategory;
    difficulty: LessonDifficulty;
    duration: string;
    order: number;
    completed: boolean;
    locked: boolean;
    current_step: number;
    total_steps: number;
}

// Puzzle types
export interface Puzzle {
    id: number;
    title: string;
    difficulty: 'easy' | 'medium' | 'hard';
    board_size: 9 | 13 | 19;
    initial_black: Coordinate[];
    initial_white: Coordinate[];
    correct_moves: Coordinate[];
    hint: string;
}
