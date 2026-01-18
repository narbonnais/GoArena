<script setup lang="ts">
import { ChevronLeft, ChevronRight, Check } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    currentStep: number;
    totalSteps: number;
    canGoNext: boolean;
    canGoPrevious: boolean;
    isLastStep: boolean;
    stepCompleted?: boolean;
}>();

const emit = defineEmits<{
    (e: 'previous'): void;
    (e: 'next'): void;
    (e: 'complete'): void;
    (e: 'go-to-step', index: number): void;
}>();

const progressPercent = computed(() => {
    if (props.totalSteps === 0) return 0;
    return ((props.currentStep + 1) / props.totalSteps) * 100;
});

// Show dots only up to 10 steps, otherwise show simplified progress
const showDots = computed(() => props.totalSteps <= 10);
</script>

<template>
    <div class="lesson-stepper">
        <!-- Progress bar -->
        <div class="progress-bar-container">
            <div class="progress-bar" :style="{ width: `${progressPercent}%` }"></div>
        </div>

        <div class="stepper-controls">
            <!-- Previous button -->
            <button
                class="stepper-btn"
                :disabled="!canGoPrevious"
                @click="emit('previous')"
            >
                <ChevronLeft :size="20" />
                <span class="btn-text">Previous</span>
            </button>

            <!-- Step indicators -->
            <div class="step-indicators">
                <template v-if="showDots">
                    <button
                        v-for="i in totalSteps"
                        :key="i"
                        class="step-dot"
                        :class="{
                            active: i - 1 === currentStep,
                            completed: i - 1 < currentStep,
                        }"
                        @click="emit('go-to-step', i - 1)"
                    >
                        <Check v-if="i - 1 < currentStep" :size="12" />
                        <span v-else class="dot-number">{{ i }}</span>
                    </button>
                </template>
                <template v-else>
                    <span class="step-count">
                        Step {{ currentStep + 1 }} of {{ totalSteps }}
                    </span>
                </template>
            </div>

            <!-- Next/Complete button -->
            <button
                v-if="!isLastStep"
                class="stepper-btn next"
                :disabled="!canGoNext || !stepCompleted"
                @click="emit('next')"
            >
                <span class="btn-text">Next</span>
                <ChevronRight :size="20" />
            </button>
            <button
                v-else
                class="stepper-btn complete"
                :disabled="!stepCompleted"
                @click="emit('complete')"
            >
                <Check :size="20" />
                <span class="btn-text">Complete</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.lesson-stepper {
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    overflow: hidden;
}

.progress-bar-container {
    height: 4px;
    background-color: var(--background);
}

.progress-bar {
    height: 100%;
    background-color: var(--go-green);
    transition: width 0.3s ease;
}

.stepper-controls {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    gap: 1rem;
}

.stepper-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.875rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--foreground);
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    min-width: 100px;
    justify-content: center;
}

.stepper-btn:hover:not(:disabled) {
    border-color: var(--foreground);
}

.stepper-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.stepper-btn.next:not(:disabled) {
    background-color: var(--go-green);
    border-color: var(--go-green);
    color: white;
}

.stepper-btn.next:hover:not(:disabled) {
    background-color: var(--go-green-hover);
    border-color: var(--go-green-hover);
}

.stepper-btn.complete:not(:disabled) {
    background-color: var(--go-green);
    border-color: var(--go-green);
    color: white;
}

.stepper-btn.complete:hover:not(:disabled) {
    background-color: var(--go-green-hover);
    border-color: var(--go-green-hover);
}

.btn-text {
    display: none;
}

@media (min-width: 480px) {
    .btn-text {
        display: inline;
    }
}

.step-indicators {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
    justify-content: center;
}

.step-dot {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    border: 2px solid var(--border);
    background-color: var(--background);
    color: var(--muted-foreground);
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
}

.step-dot:hover {
    border-color: var(--foreground);
    color: var(--foreground);
}

.step-dot.active {
    border-color: var(--go-green);
    background-color: var(--go-green);
    color: white;
}

.step-dot.completed {
    border-color: var(--go-green);
    background-color: var(--go-green);
    color: white;
}

.dot-number {
    font-size: 0.6875rem;
}

.step-count {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    font-weight: 500;
}
</style>
