<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    blackWinRate: number; // 0-100
    whiteWinRate: number; // 0-100
    isAnalyzing?: boolean;
}>();

const blackWidth = computed(() => {
    return Math.max(0, Math.min(100, props.blackWinRate));
});

const whiteWidth = computed(() => {
    return Math.max(0, Math.min(100, props.whiteWinRate));
});
</script>

<template>
    <div class="evaluation-bar-container">
        <div class="evaluation-bar" :class="{ analyzing: isAnalyzing }">
            <div class="bar-black" :style="{ width: `${blackWidth}%` }">
                <span v-if="blackWidth > 20" class="percentage">{{ blackWidth.toFixed(1) }}%</span>
            </div>
            <div class="bar-white" :style="{ width: `${whiteWidth}%` }">
                <span v-if="whiteWidth > 20" class="percentage">{{ whiteWidth.toFixed(1) }}%</span>
            </div>
        </div>
        <div class="bar-labels">
            <span class="label-black">Black</span>
            <span class="label-white">White</span>
        </div>
    </div>
</template>

<style scoped>
.evaluation-bar-container {
    width: 100%;
}

.evaluation-bar {
    display: flex;
    height: 28px;
    border-radius: 4px;
    overflow: hidden;
    background-color: var(--muted);
    position: relative;
}

.evaluation-bar.analyzing {
    opacity: 0.7;
}

.evaluation-bar.analyzing::after {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        90deg,
        transparent,
        rgba(255, 255, 255, 0.2),
        transparent
    );
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% {
        transform: translateX(-100%);
    }
    100% {
        transform: translateX(100%);
    }
}

.bar-black {
    background-color: var(--go-black, #2d2d2d);
    display: flex;
    align-items: center;
    justify-content: flex-start;
    padding-left: 8px;
    transition: width 0.3s ease;
    /* Ensure visibility in dark mode with subtle border */
    box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.1);
}

.bar-black .percentage {
    color: #ffffff;
    font-size: 0.75rem;
    font-weight: 600;
}

.bar-white {
    background-color: var(--go-white, #e8e8e8);
    display: flex;
    align-items: center;
    justify-content: flex-end;
    padding-right: 8px;
    transition: width 0.3s ease;
    /* Ensure visibility in light mode with subtle border */
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.1);
}

.bar-white .percentage {
    color: #1a1a1a;
    font-size: 0.75rem;
    font-weight: 600;
}

.bar-labels {
    display: flex;
    justify-content: space-between;
    margin-top: 4px;
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.label-black,
.label-white {
    display: flex;
    align-items: center;
    gap: 4px;
}

.label-black::before {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: var(--go-black, #2d2d2d);
    /* Ensure visibility in dark mode */
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.2);
}

.label-white::before {
    content: '';
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background-color: var(--go-white, #e8e8e8);
    border: 1px solid var(--border);
}
</style>
