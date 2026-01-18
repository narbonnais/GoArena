<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen } from 'lucide-vue-next';
import { ref, computed, watch } from 'vue';

import FeedbackOverlay from '@/components/go/learn/FeedbackOverlay.vue';
import InstructionPanel from '@/components/go/learn/InstructionPanel.vue';
import LessonStepper from '@/components/go/learn/LessonStepper.vue';
import TutorialBoard from '@/components/go/learn/TutorialBoard.vue';
import { useLesson } from '@/composables/go/useLesson';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import type { Coordinate } from '@/types/go';
import type { Lesson, LessonProgress } from '@/types/learn';

const props = defineProps<{
    lesson: Lesson;
    progress: LessonProgress | null;
}>();

// Initialize lesson composable
const lessonState = useLesson({
    lesson: props.lesson,
    initialStep: props.progress?.current_step ?? 0,
});

const {
    currentStep,
    currentStepIndex,
    totalSteps,
    board,
    boardSize,
    feedback,
    feedbackMessage,
    showHint,
    isPlayingSequence,
    canGoNext,
    canGoPrevious,
    isLastStep,
    handleMove,
    nextStep,
    previousStep,
    goToStep,
    toggleHint,
    playSequence,
    resetStep,
    allowedMoves,
    clearFeedback,
} = lessonState;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Learn', href: '/go/learn' },
    { title: props.lesson.title, href: `/go/learn/${props.lesson.slug}` },
];

// Track if current step is completed (for puzzle/placement steps)
const stepCompleted = ref(false);
const showCompletionOverlay = ref(false);

// Determine if board should be interactive
const isBoardInteractive = computed(() => {
    const step = currentStep.value;
    if (!step) return false;
    return (step.type === 'placement' || step.type === 'puzzle') && !stepCompleted.value;
});

// For instruction/demonstration steps, mark as completed immediately
watch(currentStep, (step) => {
    if (step) {
        if (step.type === 'instruction') {
            stepCompleted.value = true;
        } else if (step.type === 'demonstration') {
            // For demonstrations, complete after playing sequence
            stepCompleted.value = false;
        } else {
            stepCompleted.value = false;
        }
    }
}, { immediate: true });

// Handle stone placement
function onPlay(coord: Coordinate) {
    if (!isBoardInteractive.value) return;

    const success = handleMove(coord);
    if (success) {
        stepCompleted.value = true;
        // Clear feedback after a delay to allow moving to next step
        setTimeout(() => {
            clearFeedback();
        }, 1500);
    } else {
        // Clear incorrect feedback after a delay
        setTimeout(() => {
            clearFeedback();
        }, 1500);
    }
}

// Handle sequence playback completion
async function onPlaySequence() {
    await playSequence();
    stepCompleted.value = true;
}

// Handle step navigation
function onNextStep() {
    if (nextStep()) {
        stepCompleted.value = false;
        saveProgress();
    }
}

function onPreviousStep() {
    if (previousStep()) {
        stepCompleted.value = false;
    }
}

function onGoToStep(index: number) {
    if (goToStep(index)) {
        stepCompleted.value = false;
    }
}

// Handle lesson completion
function onComplete() {
    showCompletionOverlay.value = true;
    completeLesson();
}

function onCompletionHidden() {
    showCompletionOverlay.value = false;
    router.visit('/go/learn');
}

// Save progress to backend
function saveProgress() {
    router.post(`/go/learn/${props.lesson.slug}/progress`, {
        current_step: currentStepIndex.value,
    }, {
        preserveScroll: true,
        preserveState: true,
    });
}

// Complete lesson
function completeLesson() {
    router.post(`/go/learn/${props.lesson.slug}/complete`, {}, {
        preserveScroll: true,
        preserveState: true,
    });
}

// Handle reset
function onResetStep() {
    resetStep();
    stepCompleted.value = false;
}

// Get hints for current step
const hintCoordinates = computed(() => {
    return showHint.value ? allowedMoves.value : [];
});
</script>

<template>
    <Head :title="`${lesson.title} - Learn Go`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="lesson-detail-page">
            <!-- Header -->
            <header class="lesson-header">
                <Link href="/go/learn" class="back-link">
                    <ArrowLeft :size="20" />
                    Back to Lessons
                </Link>

                <div class="lesson-meta">
                    <span class="category-badge">{{ lesson.category }}</span>
                    <span class="difficulty-badge" :class="lesson.difficulty">{{ lesson.difficulty }}</span>
                </div>
            </header>

            <!-- Main content -->
            <div class="lesson-content">
                <!-- Left: Board -->
                <div class="board-section">
                    <TutorialBoard
                        :board="board"
                        :size="boardSize"
                        :disabled="!isBoardInteractive"
                        :highlights="currentStep?.highlights"
                        :show-hint-markers="showHint"
                        :hint-coordinates="hintCoordinates"
                        :feedback="feedback"
                        @play="onPlay"
                    />
                </div>

                <!-- Right: Instructions -->
                <div class="instruction-section">
                    <InstructionPanel
                        :step="currentStep"
                        :lesson-title="lesson.title"
                        :show-hint="showHint"
                        :feedback="feedback"
                        :feedback-message="feedbackMessage"
                        :is-playing-sequence="isPlayingSequence"
                        @toggle-hint="toggleHint"
                        @play-sequence="onPlaySequence"
                        @reset-step="onResetStep"
                    />
                </div>
            </div>

            <!-- Footer: Stepper -->
            <footer class="lesson-footer">
                <LessonStepper
                    :current-step="currentStepIndex"
                    :total-steps="totalSteps"
                    :can-go-next="canGoNext"
                    :can-go-previous="canGoPrevious"
                    :is-last-step="isLastStep"
                    :step-completed="stepCompleted"
                    @previous="onPreviousStep"
                    @next="onNextStep"
                    @complete="onComplete"
                    @go-to-step="onGoToStep"
                />
            </footer>
        </div>

        <!-- Completion overlay -->
        <FeedbackOverlay
            :type="showCompletionOverlay ? 'complete' : null"
            message="Lesson Complete!"
            :auto-hide="true"
            :auto-hide-delay="2500"
            @hidden="onCompletionHidden"
        />
    </AppLayout>
</template>

<style scoped>
.lesson-detail-page {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 64px);
    padding: 1rem;
    gap: 1rem;
}

@media (min-width: 768px) {
    .lesson-detail-page {
        padding: 1.5rem;
        gap: 1.5rem;
    }
}

.lesson-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 1rem;
}

.back-link {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--muted-foreground);
    text-decoration: none;
    transition: color 0.15s ease;
}

.back-link:hover {
    color: var(--foreground);
}

.lesson-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.category-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    background-color: var(--background);
    color: var(--muted-foreground);
    border: 1px solid var(--border);
}

.difficulty-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.difficulty-badge.beginner {
    background-color: rgba(34, 197, 94, 0.1);
    color: #16a34a;
}

.difficulty-badge.intermediate {
    background-color: rgba(234, 179, 8, 0.1);
    color: #ca8a04;
}

.difficulty-badge.advanced {
    background-color: rgba(239, 68, 68, 0.1);
    color: #dc2626;
}

.lesson-content {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    flex: 1;
}

@media (min-width: 1024px) {
    .lesson-content {
        flex-direction: row;
        align-items: flex-start;
    }
}

.board-section {
    display: flex;
    justify-content: center;
    align-items: flex-start;
}

@media (min-width: 1024px) {
    .board-section {
        flex: 0 0 auto;
    }
}

.instruction-section {
    flex: 1;
    min-width: 0;
}

@media (min-width: 1024px) {
    .instruction-section {
        max-width: 400px;
    }
}

.lesson-footer {
    margin-top: auto;
}
</style>
