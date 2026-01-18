<script setup lang="ts">
import { Check, Lock, Clock, ChevronRight } from 'lucide-vue-next';

export interface Lesson {
    id: number;
    title: string;
    description: string;
    duration: string;
    completed: boolean;
    locked: boolean;
}

defineProps<{
    lesson: Lesson;
}>();

const emit = defineEmits<{
    (e: 'click', lessonId: number): void;
}>();
</script>

<template>
    <button
        class="lesson-card"
        :class="{
            completed: lesson.completed,
            locked: lesson.locked,
        }"
        :disabled="lesson.locked"
        @click="emit('click', lesson.id)"
    >
        <div class="lesson-number">
            <Check v-if="lesson.completed" :size="18" class="check-icon" />
            <Lock v-else-if="lesson.locked" :size="16" class="lock-icon" />
            <span v-else class="number">{{ lesson.id }}</span>
        </div>
        <div class="lesson-content">
            <h3 class="lesson-title">{{ lesson.title }}</h3>
            <p class="lesson-description">{{ lesson.description }}</p>
            <div class="lesson-meta">
                <span class="lesson-duration">
                    <Clock :size="14" />
                    {{ lesson.duration }}
                </span>
            </div>
        </div>
        <ChevronRight v-if="!lesson.locked" :size="20" class="chevron" />
    </button>
</template>

<style scoped>
.lesson-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    width: 100%;
    padding: 1rem 1.25rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: left;
}

.lesson-card:hover:not(:disabled) {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.lesson-card.completed {
    border-color: var(--go-green);
}

.lesson-card.locked {
    opacity: 0.6;
    cursor: not-allowed;
}

.lesson-number {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--background);
    border-radius: 50%;
    flex-shrink: 0;
}

.lesson-card.completed .lesson-number {
    background-color: var(--go-green);
    color: white;
}

.check-icon {
    color: white;
}

.lock-icon {
    color: var(--muted-foreground);
}

.number {
    font-size: 1rem;
    font-weight: 700;
    color: var(--foreground);
}

.lesson-content {
    flex: 1;
    min-width: 0;
}

.lesson-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0 0 0.25rem;
}

.lesson-description {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0 0 0.5rem;
    line-height: 1.4;
}

.lesson-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.lesson-duration {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.chevron {
    color: var(--muted-foreground);
    flex-shrink: 0;
}

.lesson-card:hover:not(:disabled) .chevron {
    color: var(--go-green);
}
</style>
