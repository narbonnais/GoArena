<script setup lang="ts">
import { Clock } from 'lucide-vue-next';
import { ref, computed, watch, onUnmounted } from 'vue';

const props = defineProps<{
    initialTime: number; // in seconds
    isRunning: boolean;
}>();

const emit = defineEmits<{
    (e: 'timeout'): void;
}>();

const remainingTime = ref(props.initialTime);
let intervalId: ReturnType<typeof setInterval> | null = null;

const formattedTime = computed(() => {
    const minutes = Math.floor(remainingTime.value / 60);
    const seconds = remainingTime.value % 60;
    return `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
});

const isLowTime = computed(() => remainingTime.value <= 30);
const isCriticalTime = computed(() => remainingTime.value <= 10);

// Accessibility: descriptive label for screen readers
const timerAriaLabel = computed(() => {
    const minutes = Math.floor(remainingTime.value / 60);
    const seconds = remainingTime.value % 60;
    const timeDesc = minutes > 0
        ? `${minutes} minute${minutes !== 1 ? 's' : ''} and ${seconds} second${seconds !== 1 ? 's' : ''}`
        : `${seconds} second${seconds !== 1 ? 's' : ''}`;

    if (isCriticalTime.value) {
        return `Game timer: ${timeDesc} remaining. Critical time warning.`;
    }
    if (isLowTime.value) {
        return `Game timer: ${timeDesc} remaining. Low time warning.`;
    }
    return `Game timer: ${timeDesc} remaining`;
});

function startTimer() {
    if (intervalId) return;

    intervalId = setInterval(() => {
        if (remainingTime.value > 0) {
            remainingTime.value--;
        } else {
            stopTimer();
            emit('timeout');
        }
    }, 1000);
}

function stopTimer() {
    if (intervalId) {
        clearInterval(intervalId);
        intervalId = null;
    }
}

watch(() => props.isRunning, (running) => {
    if (running) {
        startTimer();
    } else {
        stopTimer();
    }
}, { immediate: true });

watch(() => props.initialTime, (newTime) => {
    remainingTime.value = newTime;
});

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <div
        class="game-timer"
        :class="{
            running: isRunning,
            'low-time': isLowTime,
            'critical-time': isCriticalTime,
        }"
        role="timer"
        :aria-label="timerAriaLabel"
        aria-live="polite"
        :aria-atomic="true"
    >
        <Clock :size="16" class="timer-icon" aria-hidden="true" />
        <span class="timer-value">{{ formattedTime }}</span>
        <!-- Screen reader announcement for critical time -->
        <span v-if="isCriticalTime" class="sr-only" role="alert">
            Warning: Less than 10 seconds remaining
        </span>
    </div>
</template>

<style scoped>
.game-timer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    font-family: 'SF Mono', 'Menlo', monospace;
}

.game-timer.running {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.game-timer.low-time {
    border-color: hsl(45 100% 50%);
    background-color: hsl(45 100% 50% / 0.15);
}

.game-timer.critical-time {
    border-color: hsl(0 84% 60%);
    background-color: hsl(0 84% 60% / 0.15);
    animation: pulse 1s ease-in-out infinite;
}

.timer-icon {
    color: var(--muted-foreground);
}

.game-timer.running .timer-icon {
    color: var(--go-green);
}

.game-timer.low-time .timer-icon {
    color: hsl(45 100% 50%);
}

.game-timer.critical-time .timer-icon {
    color: hsl(0 84% 60%);
}

.timer-value {
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
    min-width: 48px;
}

.game-timer.critical-time .timer-value {
    color: hsl(0 84% 60%);
}

@keyframes pulse {
    0%, 100% {
        opacity: 1;
    }
    50% {
        opacity: 0.7;
    }
}

/* Screen reader only - visually hidden but accessible */
.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
