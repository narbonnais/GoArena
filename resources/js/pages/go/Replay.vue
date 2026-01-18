<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, SkipBack, ChevronLeft, Play, Pause, ChevronRight, SkipForward, FlaskConical } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import GoBoard from '@/components/go/GoBoard.vue';
import GoPlayerInfo from '@/components/go/GoPlayerInfo.vue';
import { useAnalysisStudy } from '@/composables/go/useAnalysisStudy';
import { useGameReplay } from '@/composables/go/useGameReplay';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import type { SavedGame } from '@/types/go';

const props = defineProps<{
    game: SavedGame;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'History',
        href: '/go/history',
    },
    {
        title: 'Replay',
        href: `/go/history/${props.game.id}`,
    },
];

const replay = useGameReplay({ game: props.game });
const studyApi = useAnalysisStudy();
const isCreatingStudy = ref(false);

// Create analysis study from this game
async function handleAnalyze() {
    isCreatingStudy.value = true;
    const result = await studyApi.createFromGame(props.game.id);
    isCreatingStudy.value = false;

    if (result) {
        router.visit(`/go/studies/${result.id}`);
    }
}

// Format the game date
const formattedDate = computed(() => {
    const date = new Date(props.game.created_at);
    return date.toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
});

const isDraw = computed(() => props.game.winner === 'draw');
const userColor = computed(() => props.game.user_color);
const blackIsUser = computed(() => userColor.value === 'black');
const whiteIsUser = computed(() => userColor.value === 'white');

const blackPlayerName = computed(() => {
    return blackIsUser.value ? 'You' : props.game.black_player.name;
});

const whitePlayerName = computed(() => {
    return whiteIsUser.value ? 'You' : props.game.white_player.name;
});

const winnerName = computed(() => {
    if (props.game.winner === 'black') {
        return blackIsUser.value ? 'You' : props.game.black_player.name;
    }
    if (props.game.winner === 'white') {
        return whiteIsUser.value ? 'You' : props.game.white_player.name;
    }
    return 'Draw';
});

const resultTitle = computed(() => {
    if (isDraw.value) return 'Draw';
    return props.game.user_won ? 'Victory!' : 'Defeat';
});

const resultEmoji = computed(() => {
    if (isDraw.value) return '🤝';
    return props.game.user_won ? '🎉' : '😔';
});

// Format duration
const formattedDuration = computed(() => {
    const seconds = props.game.duration_seconds ?? 0;
    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;
    if (minutes === 0) {
        return `${remainingSeconds}s`;
    }
    return `${minutes}m ${remainingSeconds}s`;
});

// Game result message
const resultMessage = computed(() => {
    if (props.game.winner === 'draw') {
        return 'Game ended in a draw';
    }

    switch (props.game.end_reason) {
        case 'resignation':
            return `${winnerName.value} won by resignation`;
        case 'timeout':
            return `${winnerName.value} won on time`;
        case 'abandonment':
            return `${winnerName.value} won by abandonment`;
        default: {
            const margin = props.game.score_margin ?? 0;
            return `${winnerName.value} won by ${margin} points`;
        }
    }
});

const blackScoreLabel = computed(() => {
    return blackIsUser.value
        ? 'Black (You)'
        : `Black (${props.game.black_player.name})`;
});

const whiteScoreLabel = computed(() => {
    return whiteIsUser.value
        ? 'White (You)'
        : `White (${props.game.white_player.name})`;
});

// Handle slider change
function handleSliderChange(event: Event) {
    const target = event.target as HTMLInputElement;
    replay.goToMove(parseInt(target.value, 10));
}
</script>

<template>
    <Head title="Replay Game" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="replay-page">
            <!-- Header -->
            <div class="replay-header">
                <Link href="/go/history" class="back-btn">
                    <ArrowLeft :size="18" />
                    <span>Back</span>
                </Link>
                <div class="game-meta">
                    <span class="board-size">{{ game.board_size }}×{{ game.board_size }}</span>
                    <span class="separator">•</span>
                    <span class="date">{{ formattedDate }}</span>
                </div>
                <button
                    class="analyze-btn"
                    :disabled="isCreatingStudy"
                    @click="handleAnalyze"
                >
                    <FlaskConical :size="18" />
                    <span>{{ isCreatingStudy ? 'Creating...' : 'Analyze' }}</span>
                </button>
            </div>

            <!-- Main replay area -->
            <div class="replay-area">
                <!-- Left player info (Black - Human) -->
                <div class="player-panel left-panel">
                    <GoPlayerInfo
                        color="black"
                        :name="blackPlayerName"
                        :captures="replay.blackCaptures.value"
                        :is-current-player="replay.currentPlayer.value === 'black' && !replay.isAtEnd.value"
                    />
                </div>

                <!-- Board -->
                <div class="board-wrapper">
                    <GoBoard
                        :board="replay.board.value"
                        :size="game.board_size"
                        :current-player="replay.currentPlayer.value"
                        :last-move="replay.lastMove.value"
                        :disabled="true"
                    />
                </div>

                <!-- Right player info (White - AI) -->
                <div class="player-panel right-panel">
                    <GoPlayerInfo
                        color="white"
                        :name="whitePlayerName"
                        :captures="replay.whiteCaptures.value"
                        :is-current-player="replay.currentPlayer.value === 'white' && !replay.isAtEnd.value"
                    />
                </div>
            </div>

            <!-- Controls Panel -->
            <div class="controls-panel">
                <!-- Timeline -->
                <div class="timeline">
                    <div class="timeline-info">
                        <span class="move-count">Move {{ replay.currentMoveIndex.value }} / {{ replay.totalMoves.value }}</span>
                        <span class="duration">{{ formattedDuration }}</span>
                    </div>
                    <input
                        type="range"
                        :min="0"
                        :max="replay.totalMoves.value"
                        :value="replay.currentMoveIndex.value"
                        @input="handleSliderChange"
                        class="timeline-slider"
                    />
                </div>

                <!-- Playback controls -->
                <div class="playback-controls">
                    <button
                        @click="replay.goToStart"
                        :disabled="replay.isAtStart.value"
                        class="control-btn"
                        title="Go to start"
                    >
                        <SkipBack :size="20" />
                    </button>
                    <button
                        @click="replay.prevMove"
                        :disabled="replay.isAtStart.value"
                        class="control-btn"
                        title="Previous move"
                    >
                        <ChevronLeft :size="24" />
                    </button>
                    <button
                        @click="replay.toggleAutoPlay"
                        class="control-btn play-btn"
                        :title="replay.isPlaying.value ? 'Pause' : 'Play'"
                    >
                        <Pause v-if="replay.isPlaying.value" :size="24" />
                        <Play v-else :size="24" />
                    </button>
                    <button
                        @click="replay.nextMove"
                        :disabled="replay.isAtEnd.value"
                        class="control-btn"
                        title="Next move"
                    >
                        <ChevronRight :size="24" />
                    </button>
                    <button
                        @click="replay.goToEnd"
                        :disabled="replay.isAtEnd.value"
                        class="control-btn"
                        title="Go to end"
                    >
                        <SkipForward :size="20" />
                    </button>
                </div>

                <!-- Speed controls -->
                <div class="speed-controls">
                    <span class="speed-label">Speed:</span>
                    <button
                        v-for="speed in [{ value: 2000, label: '0.5x' }, { value: 1000, label: '1x' }, { value: 500, label: '2x' }, { value: 250, label: '4x' }]"
                        :key="speed.value"
                        @click="replay.setAutoPlaySpeed(speed.value)"
                        class="speed-btn"
                        :class="{ active: replay.autoPlaySpeed.value === speed.value }"
                    >
                        {{ speed.label }}
                    </button>
                </div>
            </div>

            <!-- Game result (shown at end) -->
            <div v-if="replay.isAtEnd.value" class="result-card">
                <div class="result-header">
                    <span class="result-emoji">{{ resultEmoji }}</span>
                    <h2 class="result-title" :class="{ win: game.user_won, draw: isDraw }">
                        {{ resultTitle }}
                    </h2>
                </div>
                <p class="result-message">{{ resultMessage }}</p>
                <div class="result-scores">
                    <div class="score-box">
                        <div class="score-stone black"></div>
                        <div class="score-details">
                            <span class="score-name">{{ blackScoreLabel }}</span>
                            <span class="score-value">{{ game.black_score }}</span>
                        </div>
                    </div>
                    <div class="score-vs">vs</div>
                    <div class="score-box">
                        <div class="score-stone white"></div>
                        <div class="score-details">
                            <span class="score-name">{{ whiteScoreLabel }}</span>
                            <span class="score-value">{{ game.white_score }} <small>(+{{ game.komi }})</small></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
.replay-page {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    padding: 1rem;
    max-width: 1200px;
    margin: 0 auto;
}

/* Header */
.replay-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background-color: var(--card);
    border-radius: 0.75rem;
    border: 1px solid var(--border);
}

.back-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--muted-foreground);
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.15s ease;
}

.back-btn:hover {
    color: var(--foreground);
    border-color: var(--foreground);
}

.game-meta {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.board-size {
    font-weight: 700;
    color: var(--foreground);
}

.separator {
    opacity: 0.5;
}

.analyze-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    background-color: #3b82f6;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.analyze-btn:hover:not(:disabled) {
    background-color: #2563eb;
}

.analyze-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Replay area */
.replay-area {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
}

@media (min-width: 1024px) {
    .replay-area {
        flex-direction: row;
        justify-content: center;
        gap: 2rem;
    }

    .left-panel { order: 1; }
    .board-wrapper { order: 2; }
    .right-panel { order: 3; }
}

.player-panel {
    order: 2;
}

.board-wrapper {
    order: 1;
}

/* Controls Panel */
.controls-panel {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    padding: 1.25rem;
    background-color: var(--card);
    border-radius: 0.75rem;
    border: 1px solid var(--border);
    max-width: 500px;
    margin: 0 auto;
    width: 100%;
}

/* Timeline */
.timeline {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.timeline-info {
    display: flex;
    justify-content: space-between;
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.move-count {
    font-weight: 600;
    color: var(--foreground);
}

.timeline-slider {
    width: 100%;
    height: 6px;
    border-radius: 3px;
    background: var(--border);
    appearance: none;
    cursor: pointer;
}

.timeline-slider::-webkit-slider-thumb {
    appearance: none;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--go-green);
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3);
}

.timeline-slider::-moz-range-thumb {
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: var(--go-green);
    cursor: pointer;
    border: none;
}

/* Playback controls */
.playback-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.control-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background-color: var(--secondary);
    border: 1px solid var(--border);
    color: var(--foreground);
    cursor: pointer;
    transition: all 0.15s ease;
}

.control-btn:hover:not(:disabled) {
    background-color: var(--accent);
}

.control-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.control-btn.play-btn {
    width: 56px;
    height: 56px;
    background-color: var(--go-green);
    border-color: var(--go-green);
    color: white;
}

.control-btn.play-btn:hover {
    background-color: var(--go-green-hover);
}

/* Speed controls */
.speed-controls {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.speed-label {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin-right: 0.25rem;
}

.speed-btn {
    padding: 0.375rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 9999px;
    background-color: var(--secondary);
    border: 1px solid var(--border);
    color: var(--muted-foreground);
    cursor: pointer;
    transition: all 0.15s ease;
}

.speed-btn:hover {
    background-color: var(--accent);
    color: var(--foreground);
}

.speed-btn.active {
    background-color: var(--go-green);
    border-color: var(--go-green);
    color: white;
}

/* Result card */
.result-card {
    padding: 1.5rem;
    background-color: var(--card);
    border-radius: 0.75rem;
    border: 1px solid var(--border);
    text-align: center;
    max-width: 400px;
    margin: 0 auto;
}

.result-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
}

.result-emoji {
    font-size: 2rem;
}

.result-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0;
}

.result-title.win {
    color: var(--go-green);
}

.result-title.draw {
    color: var(--muted-foreground);
}

.result-message {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0 0 1rem;
}

.result-scores {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1rem;
    background-color: var(--background);
    border-radius: 0.5rem;
}

.score-box {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.score-stone {
    width: 24px;
    height: 24px;
    border-radius: 50%;
}

.score-stone.black {
    background: radial-gradient(circle at 30% 30%, #4a4a4a, #1a1a1a);
}

.score-stone.white {
    background: radial-gradient(circle at 30% 30%, #fff, #e0e0e0);
    border: 1px solid #ccc;
}

.score-details {
    display: flex;
    flex-direction: column;
    text-align: left;
}

.score-name {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.score-value {
    font-size: 1rem;
    font-weight: 700;
    color: var(--foreground);
}

.score-value small {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    font-weight: 400;
}

.score-vs {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    font-weight: 600;
}
</style>
