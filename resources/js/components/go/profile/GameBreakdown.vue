<script setup lang="ts">
import { computed } from 'vue';

// Mock data - in real app this would come from backend
const breakdown = {
    '9x9': { wins: 15, losses: 8 },
    '13x13': { wins: 8, losses: 5 },
    '19x19': { wins: 5, losses: 1 },
};

const maxGames = computed(() => {
    return Math.max(
        ...Object.values(breakdown).map(b => b.wins + b.losses)
    );
});

function getBarWidth(wins: number, losses: number) {
    const total = wins + losses;
    if (maxGames.value === 0) return { wins: 0, losses: 0 };
    const scale = (total / maxGames.value) * 100;
    const winPercent = (wins / total) * scale;
    const lossPercent = (losses / total) * scale;
    return { wins: winPercent, losses: lossPercent };
}
</script>

<template>
    <div class="game-breakdown">
        <h3 class="breakdown-title">Games by Board Size</h3>

        <div class="breakdown-list">
            <div v-for="(data, size) in breakdown" :key="size" class="breakdown-item">
                <span class="board-size">{{ size }}</span>
                <div class="bar-container">
                    <div class="bar">
                        <div
                            class="bar-segment wins"
                            :style="{ width: `${getBarWidth(data.wins, data.losses).wins}%` }"
                        >
                            <span v-if="data.wins > 0" class="segment-label">{{ data.wins }}</span>
                        </div>
                        <div
                            class="bar-segment losses"
                            :style="{ width: `${getBarWidth(data.wins, data.losses).losses}%` }"
                        >
                            <span v-if="data.losses > 0" class="segment-label">{{ data.losses }}</span>
                        </div>
                    </div>
                </div>
                <span class="total-games">{{ data.wins + data.losses }}</span>
            </div>
        </div>

        <div class="breakdown-legend">
            <div class="legend-item">
                <span class="legend-color wins"></span>
                <span class="legend-label">Wins</span>
            </div>
            <div class="legend-item">
                <span class="legend-color losses"></span>
                <span class="legend-label">Losses</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.game-breakdown {
    padding: 1.25rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
}

.breakdown-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0 0 1rem;
}

.breakdown-list {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.breakdown-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.board-size {
    width: 48px;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--foreground);
}

.bar-container {
    flex: 1;
}

.bar {
    display: flex;
    height: 24px;
    background-color: var(--background);
    border-radius: 0.375rem;
    overflow: hidden;
}

.bar-segment {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 24px;
    transition: width 0.3s ease;
}

.bar-segment.wins {
    background-color: var(--go-green);
}

.bar-segment.losses {
    background-color: hsl(0 84% 60%);
}

.segment-label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: white;
}

.total-games {
    width: 32px;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--muted-foreground);
    text-align: right;
}

.breakdown-legend {
    display: flex;
    justify-content: center;
    gap: 1.5rem;
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border);
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.375rem;
}

.legend-color {
    width: 12px;
    height: 12px;
    border-radius: 0.25rem;
}

.legend-color.wins {
    background-color: var(--go-green);
}

.legend-color.losses {
    background-color: hsl(0 84% 60%);
}

.legend-label {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}
</style>
