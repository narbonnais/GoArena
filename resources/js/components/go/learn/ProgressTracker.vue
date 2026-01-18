<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    completed: number;
    total: number;
}>();

const percentage = computed(() => {
    if (props.total === 0) return 0;
    return Math.round((props.completed / props.total) * 100);
});

const circumference = 2 * Math.PI * 40;
const dashOffset = computed(() => {
    return circumference - (percentage.value / 100) * circumference;
});
</script>

<template>
    <div class="progress-tracker">
        <div class="progress-ring-container">
            <svg class="progress-ring" viewBox="0 0 100 100">
                <!-- Background circle -->
                <circle
                    class="progress-bg"
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    stroke-width="8"
                />
                <!-- Progress circle -->
                <circle
                    class="progress-fill"
                    cx="50"
                    cy="50"
                    r="40"
                    fill="none"
                    stroke-width="8"
                    :stroke-dasharray="circumference"
                    :stroke-dashoffset="dashOffset"
                />
            </svg>
            <div class="progress-text">
                <span class="progress-value">{{ percentage }}%</span>
            </div>
        </div>
        <div class="progress-info">
            <span class="progress-label">{{ completed }} of {{ total }} lessons completed</span>
            <div class="progress-bar">
                <div class="progress-bar-fill" :style="{ width: `${percentage}%` }"></div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.progress-tracker {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    padding: 1.25rem 1.5rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
}

.progress-ring-container {
    position: relative;
    width: 80px;
    height: 80px;
    flex-shrink: 0;
}

.progress-ring {
    transform: rotate(-90deg);
    width: 100%;
    height: 100%;
}

.progress-bg {
    stroke: var(--border);
}

.progress-fill {
    stroke: var(--go-green);
    transition: stroke-dashoffset 0.5s ease;
    stroke-linecap: round;
}

.progress-text {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
}

.progress-info {
    flex: 1;
}

.progress-label {
    display: block;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--foreground);
    margin-bottom: 0.5rem;
}

.progress-bar {
    height: 8px;
    background-color: var(--border);
    border-radius: 4px;
    overflow: hidden;
}

.progress-bar-fill {
    height: 100%;
    background-color: var(--go-green);
    border-radius: 4px;
    transition: width 0.5s ease;
}
</style>
