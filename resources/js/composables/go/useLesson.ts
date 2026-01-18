import { ref, computed, shallowRef } from 'vue';

import type { Coordinate, Stone, BoardState, Intersection } from '@/types/go';
import type { Lesson, LessonStep } from '@/types/learn';

export interface UseLessonOptions {
    lesson: Lesson;
    initialStep?: number;
}

export type FeedbackType = 'correct' | 'incorrect' | null;

/**
 * Create an empty board of the given size
 */
function createEmptyBoard(size: number): BoardState {
    return Array.from({ length: size }, () =>
        Array.from({ length: size }, () => null as Intersection)
    );
}

/**
 * Set up board with initial positions
 */
function setupBoard(size: number, blackStones: Coordinate[], whiteStones: Coordinate[]): BoardState {
    const board = createEmptyBoard(size);

    for (const coord of blackStones) {
        if (coord.y >= 0 && coord.y < size && coord.x >= 0 && coord.x < size) {
            board[coord.y][coord.x] = 'black';
        }
    }

    for (const coord of whiteStones) {
        if (coord.y >= 0 && coord.y < size && coord.x >= 0 && coord.x < size) {
            board[coord.y][coord.x] = 'white';
        }
    }

    return board;
}

/**
 * Composable for managing lesson state and interactions
 */
export function useLesson(options: UseLessonOptions) {
    const { lesson, initialStep = 0 } = options;

    // Current step index (0-based)
    const currentStepIndex = ref(initialStep);

    // Board state
    const boardSize = ref<9 | 13 | 19>(9);
    const board = shallowRef<BoardState>(createEmptyBoard(9));

    // Feedback state
    const feedback = ref<FeedbackType>(null);
    const feedbackMessage = ref<string>('');

    // Hint visibility
    const showHint = ref(false);

    // Sequence playback state
    const isPlayingSequence = ref(false);
    const sequenceStep = ref(0);

    // Current step (computed)
    const currentStep = computed<LessonStep | null>(() => {
        if (!lesson.steps || currentStepIndex.value >= lesson.steps.length) {
            return null;
        }
        return lesson.steps[currentStepIndex.value];
    });

    // Total steps
    const totalSteps = computed(() => lesson.steps?.length ?? 0);

    // Progress percentage
    const progress = computed(() => {
        if (totalSteps.value === 0) return 0;
        return Math.round((currentStepIndex.value / totalSteps.value) * 100);
    });

    // Navigation state
    const canGoNext = computed(() => currentStepIndex.value < totalSteps.value - 1);
    const canGoPrevious = computed(() => currentStepIndex.value > 0);
    const isLastStep = computed(() => currentStepIndex.value === totalSteps.value - 1);
    const isFirstStep = computed(() => currentStepIndex.value === 0);

    // Allowed moves for current step
    const allowedMoves = computed<Coordinate[]>(() => {
        const step = currentStep.value;
        if (!step) return [];
        return step.correctMoves ?? [];
    });

    // Check if a coordinate is a valid move
    function isValidMove(coord: Coordinate): boolean {
        const step = currentStep.value;
        if (!step) return false;

        // For instruction steps, no moves allowed
        if (step.type === 'instruction' || step.type === 'demonstration') {
            return false;
        }

        // For placement/puzzle steps, check against correct moves
        return allowedMoves.value.some(c => c.x === coord.x && c.y === coord.y);
    }

    // Set up board for current step
    function setupCurrentStep() {
        const step = currentStep.value;
        if (!step) return;

        boardSize.value = step.boardSize;
        board.value = setupBoard(step.boardSize, step.initialBlack, step.initialWhite);
        feedback.value = null;
        feedbackMessage.value = '';
        showHint.value = false;
        isPlayingSequence.value = false;
        sequenceStep.value = 0;
    }

    // Handle a move attempt
    function handleMove(coord: Coordinate): boolean {
        const step = currentStep.value;
        if (!step) return false;

        // Don't allow moves during demonstrations or instructions
        if (step.type === 'instruction' || step.type === 'demonstration') {
            return false;
        }

        // Check if cell is already occupied
        if (board.value[coord.y]?.[coord.x] !== null) {
            return false;
        }

        // Check if move is correct
        const isCorrect = isValidMove(coord);

        if (isCorrect) {
            // Place the stone
            const newBoard = board.value.map(row => [...row]);
            const playerColor = step.playerColor ?? 'black';
            newBoard[coord.y][coord.x] = playerColor;
            board.value = newBoard;

            feedback.value = 'correct';
            feedbackMessage.value = 'Correct!';

            return true;
        } else {
            // Wrong move
            feedback.value = 'incorrect';
            feedbackMessage.value = step.wrongMoveResponse || 'Try again!';

            return false;
        }
    }

    // Navigate to next step
    function nextStep(): boolean {
        if (!canGoNext.value) return false;

        currentStepIndex.value++;
        setupCurrentStep();
        return true;
    }

    // Navigate to previous step
    function previousStep(): boolean {
        if (!canGoPrevious.value) return false;

        currentStepIndex.value--;
        setupCurrentStep();
        return true;
    }

    // Jump to a specific step
    function goToStep(index: number): boolean {
        if (index < 0 || index >= totalSteps.value) return false;

        currentStepIndex.value = index;
        setupCurrentStep();
        return true;
    }

    // Toggle hint visibility
    function toggleHint() {
        showHint.value = !showHint.value;
    }

    // Clear feedback
    function clearFeedback() {
        feedback.value = null;
        feedbackMessage.value = '';
    }

    // Play demonstration sequence
    async function playSequence() {
        const step = currentStep.value;
        if (!step?.sequence || step.sequence.length === 0) return;

        isPlayingSequence.value = true;
        sequenceStep.value = 0;

        for (let i = 0; i < step.sequence.length; i++) {
            const move = step.sequence[i];
            const delay = move.delay ?? 800;

            // Wait before placing
            await new Promise(resolve => setTimeout(resolve, delay));

            // Place the stone
            const newBoard = board.value.map(row => [...row]);
            newBoard[move.coordinate.y][move.coordinate.x] = move.stone;
            board.value = newBoard;
            sequenceStep.value = i + 1;
        }

        isPlayingSequence.value = false;
    }

    // Reset current step
    function resetStep() {
        setupCurrentStep();
    }

    // Initialize with first step
    setupCurrentStep();

    return {
        // State
        lesson,
        currentStepIndex,
        currentStep,
        totalSteps,
        progress,
        board,
        boardSize,
        feedback,
        feedbackMessage,
        showHint,
        isPlayingSequence,
        sequenceStep,

        // Navigation
        canGoNext,
        canGoPrevious,
        isLastStep,
        isFirstStep,
        nextStep,
        previousStep,
        goToStep,

        // Actions
        handleMove,
        isValidMove,
        toggleHint,
        clearFeedback,
        playSequence,
        resetStep,
        allowedMoves,
    };
}
