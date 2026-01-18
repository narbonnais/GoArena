<script setup lang="ts">
import { Lightbulb, Play, RotateCcw } from 'lucide-vue-next';
import { computed } from 'vue';

import type { LessonStep } from '@/types/learn';

const props = defineProps<{
    step: LessonStep | null;
    lessonTitle: string;
    showHint: boolean;
    feedback: 'correct' | 'incorrect' | null;
    feedbackMessage: string;
    isPlayingSequence?: boolean;
}>();

const emit = defineEmits<{
    (e: 'toggle-hint'): void;
    (e: 'play-sequence'): void;
    (e: 'reset-step'): void;
}>();

const hasHint = computed(() => !!props.step?.hint);
const hasSequence = computed(() => props.step?.type === 'demonstration' && props.step?.sequence?.length);
const isInteractive = computed(() => props.step?.type === 'placement' || props.step?.type === 'puzzle');

const stepTypeLabel = computed(() => {
    switch (props.step?.type) {
        case 'instruction': return 'Read';
        case 'placement': return 'Place a Stone';
        case 'puzzle': return 'Solve';
        case 'demonstration': return 'Watch';
        default: return '';
    }
});
</script>

<template>
    <div class="instruction-panel">
        <div class="panel-header">
            <h2 class="lesson-title">{{ lessonTitle }}</h2>
            <span v-if="step" class="step-type-badge">{{ stepTypeLabel }}</span>
        </div>

        <div v-if="step" class="panel-content">
            <h3 v-if="step.title" class="step-title">{{ step.title }}</h3>

            <div class="instruction-text">
                <p>{{ step.instruction }}</p>
            </div>

            <!-- Hint section -->
            <div v-if="hasHint" class="hint-section">
                <button
                    class="hint-toggle"
                    :class="{ active: showHint }"
                    @click="emit('toggle-hint')"
                >
                    <Lightbulb :size="18" />
                    {{ showHint ? 'Hide Hint' : 'Show Hint' }}
                </button>

                <div v-if="showHint" class="hint-content">
                    <p>{{ step.hint }}</p>
                </div>
            </div>

            <!-- Feedback message -->
            <div v-if="feedback" class="feedback-message" :class="feedback">
                <p>{{ feedbackMessage }}</p>
            </div>

            <!-- Action buttons for specific step types -->
            <div class="action-buttons">
                <button
                    v-if="hasSequence"
                    class="action-btn primary"
                    :disabled="isPlayingSequence"
                    @click="emit('play-sequence')"
                >
                    <Play :size="18" />
                    {{ isPlayingSequence ? 'Playing...' : 'Play Sequence' }}
                </button>

                <button
                    v-if="isInteractive"
                    class="action-btn secondary"
                    @click="emit('reset-step')"
                >
                    <RotateCcw :size="18" />
                    Reset
                </button>
            </div>
        </div>

        <div v-else class="panel-empty">
            <p>No step loaded</p>
        </div>
    </div>
</template>

<style scoped>
.instruction-panel {
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background-color: var(--background);
    border-bottom: 1px solid var(--border);
}

.lesson-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0;
}

.step-type-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.panel-content {
    padding: 1.25rem;
    flex: 1;
    overflow-y: auto;
}

.step-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0 0 0.75rem;
}

.instruction-text {
    margin-bottom: 1rem;
}

.instruction-text p {
    font-size: 0.9375rem;
    line-height: 1.6;
    color: var(--foreground);
    margin: 0;
}

.hint-section {
    margin-bottom: 1rem;
}

.hint-toggle {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--muted-foreground);
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.hint-toggle:hover {
    border-color: var(--go-green);
    color: var(--go-green);
}

.hint-toggle.active {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.hint-content {
    margin-top: 0.75rem;
    padding: 0.75rem 1rem;
    background-color: rgba(234, 179, 8, 0.1);
    border-left: 3px solid #eab308;
    border-radius: 0 0.375rem 0.375rem 0;
}

.hint-content p {
    font-size: 0.875rem;
    color: var(--foreground);
    margin: 0;
}

.feedback-message {
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin-bottom: 1rem;
}

.feedback-message.correct {
    background-color: rgba(34, 197, 94, 0.1);
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.feedback-message.correct p {
    color: #16a34a;
}

.feedback-message.incorrect {
    background-color: rgba(239, 68, 68, 0.1);
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.feedback-message.incorrect p {
    color: #dc2626;
}

.feedback-message p {
    font-size: 0.875rem;
    font-weight: 500;
    margin: 0;
}

.action-buttons {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.action-btn.primary {
    background-color: var(--go-green);
    color: white;
    border: none;
}

.action-btn.primary:hover:not(:disabled) {
    background-color: var(--go-green-hover);
}

.action-btn.primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.action-btn.secondary {
    background-color: transparent;
    color: var(--muted-foreground);
    border: 1px solid var(--border);
}

.action-btn.secondary:hover {
    border-color: var(--foreground);
    color: var(--foreground);
}

.panel-empty {
    padding: 2rem;
    text-align: center;
    color: var(--muted-foreground);
}
</style>
