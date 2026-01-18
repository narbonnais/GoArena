<script setup lang="ts">
import { Loader2, Lightbulb } from 'lucide-vue-next';
import { computed } from 'vue';

import type { AnalysisResult, Stone } from '@/types/go';

import GoEvaluationBar from './GoEvaluationBar.vue';

const props = defineProps<{
    analysis: AnalysisResult | null;
    isAnalyzing: boolean;
    currentPlayer: Stone;
    showSuggestedMove: boolean;
    boardSize: number;
}>();

const emit = defineEmits<{
    (e: 'ask-ai'): void;
    (e: 'toggle-suggestion'): void;
}>();

// Convert coordinate to display format (e.g., D4)
function coordToLabel(coord: { x: number; y: number }): string {
    const letters = 'ABCDEFGHJKLMNOPQRST'; // Skip 'I' in Go
    const col = letters[coord.x] ?? '?';
    const row = props.boardSize - coord.y;
    return `${col}${row}`;
}

const scoreDisplay = computed(() => {
    if (!props.analysis) return null;

    const { lead, winner } = props.analysis.scoreEstimate;
    const absLead = Math.abs(lead);

    if (absLead < 0.5) {
        return 'Even';
    }

    return `${winner === 'black' ? 'B' : 'W'}+${absLead.toFixed(1)}`;
});

const turnLabel = computed(() => {
    return props.currentPlayer === 'black' ? 'Black to play' : 'White to play';
});
</script>

<template>
    <div class="analysis-panel">
        <div class="panel-header">
            <h3 class="panel-title">Analysis</h3>
            <span class="turn-indicator" :class="currentPlayer">
                {{ turnLabel }}
            </span>
        </div>

        <div class="panel-content">
            <!-- Win Rate Bar -->
            <div class="section">
                <h4 class="section-title">Win Rate</h4>
                <GoEvaluationBar
                    :black-win-rate="analysis?.winRate.black ?? 50"
                    :white-win-rate="analysis?.winRate.white ?? 50"
                    :is-analyzing="isAnalyzing"
                />
            </div>

            <!-- Score Estimate -->
            <div class="section">
                <h4 class="section-title">Score Estimate</h4>
                <div class="score-display" :class="{ analyzing: isAnalyzing }">
                    <span v-if="isAnalyzing" class="analyzing-text">
                        <Loader2 class="spin" :size="16" />
                        Analyzing...
                    </span>
                    <span v-else-if="analysis" class="score-value" :class="analysis.scoreEstimate.winner">
                        {{ scoreDisplay }}
                    </span>
                    <span v-else class="no-analysis">--</span>
                </div>
            </div>

            <!-- Top Moves -->
            <div class="section">
                <h4 class="section-title">Top Moves</h4>
                <div v-if="isAnalyzing" class="top-moves-loading">
                    <Loader2 class="spin" :size="16" />
                    <span>Calculating...</span>
                </div>
                <div v-else-if="analysis && analysis.topMoves.length > 0" class="top-moves-list">
                    <div
                        v-for="(move, index) in analysis.topMoves"
                        :key="index"
                        class="move-item"
                    >
                        <span class="move-rank">{{ move.rank }}</span>
                        <span class="move-coord">{{ coordToLabel(move.coordinate) }}</span>
                        <span class="move-winrate">{{ move.winRate.toFixed(1) }}%</span>
                    </div>
                </div>
                <div v-else class="no-moves">
                    No analysis available
                </div>
            </div>

            <!-- Ask AI Button -->
            <div class="section actions">
                <button
                    class="ask-ai-btn"
                    :disabled="isAnalyzing"
                    :class="{ active: showSuggestedMove }"
                    @click="emit('ask-ai')"
                >
                    <Lightbulb :size="18" />
                    <span>{{ showSuggestedMove ? 'Hide Suggestion' : 'Show Best Move' }}</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.analysis-panel {
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    overflow: hidden;
    width: 280px;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background-color: var(--background-deep);
    border-bottom: 1px solid var(--border);
}

.panel-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0;
}

.turn-indicator {
    font-size: 0.75rem;
    font-weight: 500;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.turn-indicator.black {
    background-color: #1a1a1a;
    color: #ffffff;
}

.turn-indicator.white {
    background-color: #f5f5f5;
    color: #1a1a1a;
    border: 1px solid var(--border);
}

.panel-content {
    padding: 1rem;
}

.section {
    margin-bottom: 1rem;
}

.section:last-child {
    margin-bottom: 0;
}

.section-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted-foreground);
    margin: 0 0 0.5rem 0;
}

.score-display {
    font-size: 1.5rem;
    font-weight: 700;
    text-align: center;
    padding: 0.5rem;
    background-color: var(--background);
    border-radius: 0.5rem;
}

.score-display.analyzing {
    opacity: 0.7;
}

.analyzing-text {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--muted-foreground);
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}

.score-value.black {
    color: #1a1a1a;
}

.score-value.white {
    color: #666666;
}

.no-analysis {
    color: var(--muted-foreground);
}

.top-moves-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    color: var(--muted-foreground);
    font-size: 0.875rem;
}

.top-moves-list {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.move-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background-color: var(--background);
    border-radius: 0.375rem;
    font-size: 0.875rem;
}

.move-item:first-child {
    background-color: var(--go-green);
    background-color: rgba(34, 197, 94, 0.15);
}

.move-rank {
    width: 1.5rem;
    height: 1.5rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--muted);
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--foreground);
}

.move-item:first-child .move-rank {
    background-color: var(--go-green);
    color: white;
}

.move-coord {
    font-weight: 600;
    color: var(--foreground);
    flex: 1;
}

.move-winrate {
    color: var(--muted-foreground);
    font-size: 0.75rem;
}

.no-moves {
    text-align: center;
    padding: 1rem;
    color: var(--muted-foreground);
    font-size: 0.875rem;
}

.actions {
    margin-top: 1.5rem;
}

.ask-ai-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--foreground);
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.ask-ai-btn:hover:not(:disabled) {
    background-color: var(--accent);
    border-color: var(--go-green);
}

.ask-ai-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.ask-ai-btn.active {
    background-color: var(--go-green);
    color: white;
    border-color: var(--go-green);
}
</style>
