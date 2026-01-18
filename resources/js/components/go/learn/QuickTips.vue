<script setup lang="ts">
import { Lightbulb, ChevronLeft, ChevronRight } from 'lucide-vue-next';
import { ref, onMounted, onUnmounted } from 'vue';

const tips = [
    {
        title: 'Capture Stones',
        content: 'Surround your opponent\'s stones on all sides to capture them and remove them from the board.',
    },
    {
        title: 'Ko Rule',
        content: 'The Ko rule prevents immediate recapture. Wait at least one turn before recapturing a Ko.',
    },
    {
        title: 'Territory',
        content: 'The goal is to surround more empty intersections than your opponent. These become your territory.',
    },
    {
        title: 'Liberties',
        content: 'Each stone needs at least one liberty (adjacent empty point) to stay alive on the board.',
    },
];

const currentTipIndex = ref(0);
let intervalId: ReturnType<typeof setInterval> | null = null;

function nextTip() {
    currentTipIndex.value = (currentTipIndex.value + 1) % tips.length;
}

function prevTip() {
    currentTipIndex.value = (currentTipIndex.value - 1 + tips.length) % tips.length;
}

onMounted(() => {
    intervalId = setInterval(nextTip, 8000);
});

onUnmounted(() => {
    if (intervalId) {
        clearInterval(intervalId);
    }
});
</script>

<template>
    <div class="quick-tips">
        <div class="tips-header">
            <Lightbulb :size="18" class="tips-icon" />
            <span class="tips-label">Quick Tips</span>
        </div>
        <div class="tips-content">
            <transition name="fade" mode="out-in">
                <div :key="currentTipIndex" class="tip">
                    <h4 class="tip-title">{{ tips[currentTipIndex].title }}</h4>
                    <p class="tip-content">{{ tips[currentTipIndex].content }}</p>
                </div>
            </transition>
        </div>
        <div class="tips-nav">
            <button class="nav-btn" @click="prevTip">
                <ChevronLeft :size="16" />
            </button>
            <div class="tip-indicators">
                <span
                    v-for="(_, index) in tips"
                    :key="index"
                    class="indicator"
                    :class="{ active: index === currentTipIndex }"
                    @click="currentTipIndex = index"
                ></span>
            </div>
            <button class="nav-btn" @click="nextTip">
                <ChevronRight :size="16" />
            </button>
        </div>
    </div>
</template>

<style scoped>
.quick-tips {
    padding: 1rem 1.25rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
}

.tips-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.tips-icon {
    color: var(--go-green);
}

.tips-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.tips-content {
    min-height: 80px;
}

.tip-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0 0 0.5rem;
}

.tip-content {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    line-height: 1.5;
    margin: 0;
}

.tips-nav {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 1rem;
}

.nav-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.375rem;
    cursor: pointer;
    color: var(--muted-foreground);
    transition: all 0.15s ease;
}

.nav-btn:hover {
    background-color: var(--accent);
    color: var(--foreground);
}

.tip-indicators {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.indicator {
    width: 6px;
    height: 6px;
    background-color: var(--border);
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.15s ease;
}

.indicator.active {
    background-color: var(--go-green);
    width: 20px;
    border-radius: 3px;
}

.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
