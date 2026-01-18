<script setup lang="ts">

import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Loader2, PlayCircle, Swords, Users } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

import { useMatchmaking } from '@/composables/go/useMatchmaking';
import type { MatchFoundPayload, TimeControl, UserRating } from '@/types/multiplayer';

interface ActiveGame {
    id: number;
    opponent: string | null;
    board_size: number;
    your_color: 'black' | 'white';
}

// Props from Inertia
const props = defineProps<{
    timeControls: TimeControl[];
    ratings?: Record<number, UserRating>;
    activeGame?: ActiveGame | null;
}>();

// State
const selectedBoardSize = ref<9 | 13 | 19>(19);
const selectedTimeControl = ref<TimeControl | null>(null);
const isRanked = ref(true);

// Matchmaking
const matchmaking = useMatchmaking({
    onMatchFound: handleMatchFound,
});

// Computed
const currentRating = computed(() => {
    return props.ratings?.[selectedBoardSize.value]?.rating ?? 700;
});

const currentRankTitle = computed(() => {
    return props.ratings?.[selectedBoardSize.value]?.rank_title ?? '?';
});

// Group time controls by category
const timeControlsByCategory = computed(() => {
    const categories: Record<string, TimeControl[]> = {
        Bullet: [],
        Blitz: [],
        Rapid: [],
        Classical: [],
    };

    for (const tc of props.timeControls) {
        if (tc.slug.startsWith('bullet')) {
            categories.Bullet.push(tc);
        } else if (tc.slug.startsWith('blitz')) {
            categories.Blitz.push(tc);
        } else if (tc.slug.startsWith('rapid')) {
            categories.Rapid.push(tc);
        } else {
            categories.Classical.push(tc);
        }
    }

    return categories;
});

// Methods
function handleMatchFound(payload: MatchFoundPayload) {
    // Navigate to the game page
    router.visit(`/multiplayer/${payload.game_id}`);
}

async function startMatchmaking() {
    if (!selectedTimeControl.value) return;

    await matchmaking.joinQueue(
        selectedBoardSize.value,
        selectedTimeControl.value,
        isRanked.value
    );
}

async function cancelMatchmaking() {
    await matchmaking.leaveQueue();
}

function selectTimeControl(tc: TimeControl) {
    selectedTimeControl.value = tc;
}

// Check queue status on mount
onMounted(async () => {
    // Set default time control
    if (props.timeControls.length > 0) {
        // Default to Rapid 10+5
        selectedTimeControl.value =
            props.timeControls.find((tc) => tc.slug === 'rapid-10-5') ?? props.timeControls[0];
    }

    // Check if already in queue
    await matchmaking.getStatus();
});
</script>

<template>
    <Head title="Multiplayer" />

    <div class="multiplayer-page">
        <!-- Header -->
        <header class="page-header">
            <button class="back-btn" @click="router.visit('/go')">
                <ArrowLeft :size="18" />
                <span>Back</span>
            </button>
            <h1 class="page-title">
                <Swords :size="24" />
                <span>Multiplayer</span>
            </h1>
            <div class="header-spacer"></div>
        </header>

        <!-- Main Content -->
        <main class="page-content">
            <!-- Searching Overlay -->
            <div v-if="matchmaking.isInQueue.value" class="searching-overlay">
                <div class="searching-modal">
                    <div class="searching-animation">
                        <Loader2 :size="48" class="spinner" />
                    </div>
                    <h2>Searching for opponent...</h2>
                    <p class="search-info">
                        {{ selectedBoardSize }}x{{ selectedBoardSize }} |
                        {{ selectedTimeControl?.display_time }} |
                        {{ isRanked ? 'Ranked' : 'Casual' }}
                    </p>
                    <p class="wait-time">{{ matchmaking.waitTimeFormatted.value }}</p>
                    <button class="btn btn-secondary" @click="cancelMatchmaking">Cancel</button>
                </div>
            </div>

            <!-- Match Found Animation -->
            <div v-if="matchmaking.matchFound.value" class="match-found-overlay">
                <div class="match-found-modal">
                    <div class="match-found-animation">
                        <Users :size="48" />
                    </div>
                    <h2>Match Found!</h2>
                    <p>Playing as {{ matchmaking.matchFound.value.your_color }}</p>
                    <p class="opponent-info">
                        vs {{ matchmaking.matchFound.value.opponent.name }}
                        <span v-if="matchmaking.matchFound.value.opponent.rating">
                            ({{ matchmaking.matchFound.value.opponent.rating }})
                        </span>
                    </p>
                </div>
            </div>

            <!-- Setup Panel -->
            <div v-if="!matchmaking.isInQueue.value" class="setup-panel">
                <!-- Active Game Banner -->
                <div v-if="activeGame" class="active-game-banner">
                    <div class="active-game-info">
                        <span class="active-game-text">
                            You have an active game
                            <template v-if="activeGame.opponent">
                                vs <strong>{{ activeGame.opponent }}</strong>
                            </template>
                        </span>
                        <span class="active-game-details">
                            {{ activeGame.board_size }}x{{ activeGame.board_size }} · Playing as {{ activeGame.your_color }}
                        </span>
                    </div>
                    <button
                        class="btn btn-primary"
                        @click="router.visit(`/multiplayer/${activeGame.id}`)"
                    >
                        <PlayCircle :size="18" />
                        <span>Resume Game</span>
                    </button>
                </div>

                <!-- Play Button -->
                <div class="play-action">
                    <button
                        class="btn btn-primary btn-large"
                        :disabled="!selectedTimeControl || matchmaking.isSearching.value || !!activeGame"
                        @click="startMatchmaking"
                    >
                        <Loader2 v-if="matchmaking.isSearching.value" :size="20" class="spinner" />
                        <Swords v-else :size="20" />
                        <span>{{ matchmaking.isSearching.value ? 'Starting...' : 'Find Match' }}</span>
                    </button>
                </div>

                <!-- Error Display -->
                <div v-if="matchmaking.error.value" class="error-message">
                    {{ matchmaking.error.value }}
                </div>

                <!-- Board Size Selection -->
                <section class="setup-section">
                    <h2 class="section-title">Board Size</h2>
                    <div class="board-size-options">
                        <button
                            v-for="size in [9, 13, 19] as const"
                            :key="size"
                            class="size-btn"
                            :class="{ active: selectedBoardSize === size }"
                            @click="selectedBoardSize = size"
                        >
                            <span class="size-value">{{ size }}x{{ size }}</span>
                            <span class="size-label">{{
                                size === 9 ? 'Quick' : size === 13 ? 'Medium' : 'Full'
                            }}</span>
                        </button>
                    </div>
                </section>

                <!-- Time Control Selection -->
                <section class="setup-section">
                    <h2 class="section-title">
                        <Clock :size="18" />
                        Time Control
                    </h2>
                    <div class="time-control-categories">
                        <div
                            v-for="(controls, category) in timeControlsByCategory"
                            :key="category"
                            class="time-category"
                        >
                            <h3 class="category-title">{{ category }}</h3>
                            <div class="time-options">
                                <button
                                    v-for="tc in controls"
                                    :key="tc.id"
                                    class="time-btn"
                                    :class="{ active: selectedTimeControl?.id === tc.id }"
                                    @click="selectTimeControl(tc)"
                                >
                                    {{ tc.display_time }}
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Game Mode Selection -->
                <section class="setup-section">
                    <h2 class="section-title">Game Mode</h2>
                    <div class="mode-options">
                        <button
                            class="mode-btn"
                            :class="{ active: isRanked }"
                            @click="isRanked = true"
                        >
                            <span class="mode-name">Ranked</span>
                            <span class="mode-desc">Affects your rating</span>
                        </button>
                        <button
                            class="mode-btn"
                            :class="{ active: !isRanked }"
                            @click="isRanked = false"
                        >
                            <span class="mode-name">Casual</span>
                            <span class="mode-desc">No rating change</span>
                        </button>
                    </div>
                </section>

                <!-- Your Rating -->
                <section class="setup-section rating-section">
                    <div class="rating-display">
                        <span class="rating-label">Your Rating ({{ selectedBoardSize }}x{{ selectedBoardSize }})</span>
                        <span class="rating-value">{{ currentRating }}</span>
                        <span class="rank-badge">{{ currentRankTitle }}</span>
                    </div>
                </section>

            </div>
        </main>
    </div>
</template>

<style scoped>
.multiplayer-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background-color: var(--background);
}

.page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.5rem;
    background-color: var(--background-deep);
    border-bottom: 1px solid var(--border);
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
    cursor: pointer;
    transition: all 0.15s ease;
}

.back-btn:hover {
    color: var(--foreground);
    border-color: var(--foreground);
}

.page-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0;
}

.header-spacer {
    width: 80px;
}

.page-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 2rem 1rem;
}

.setup-panel {
    width: 100%;
    max-width: 600px;
    display: flex;
    flex-direction: column;
    gap: 2rem;
}

.setup-section {
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 1rem;
    padding: 1.5rem;
}

.section-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0 0 1rem;
}

.board-size-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.size-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 1rem;
    background-color: var(--background);
    border: 2px solid var(--border);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.size-btn:hover {
    border-color: var(--muted-foreground);
}

.size-btn.active {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.size-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
}

.size-label {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.time-control-categories {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.time-category {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.category-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin: 0;
}

.time-options {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.time-btn {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--foreground);
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.time-btn:hover {
    border-color: var(--muted-foreground);
}

.time-btn.active {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.mode-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.mode-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 1rem;
    background-color: var(--background);
    border: 2px solid var(--border);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.mode-btn:hover {
    border-color: var(--muted-foreground);
}

.mode-btn.active {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.mode-name {
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
}

.mode-desc {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.rating-section {
    text-align: center;
}

.rating-display {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.rating-label {
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.rating-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--foreground);
}

.rank-badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--go-green);
    background-color: var(--go-green-muted);
    border-radius: 9999px;
}

.play-action {
    display: flex;
    justify-content: center;
}

.btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
}

.btn-primary {
    background-color: var(--go-green);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--go-green-hover);
}

.btn-primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-secondary {
    background-color: var(--muted);
    color: var(--foreground);
}

.btn-secondary:hover {
    background-color: var(--accent);
}

.btn-large {
    padding: 1rem 2rem;
    font-size: 1.125rem;
}

.spinner {
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

.error-message {
    text-align: center;
    padding: 0.75rem;
    font-size: 0.875rem;
    color: hsl(0 84% 60%);
    background-color: hsl(0 84% 60% / 0.1);
    border-radius: 0.5rem;
}

.active-game-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 1rem 1.25rem;
    background-color: var(--go-green-muted);
    border: 1px solid var(--go-green);
    border-radius: 0.75rem;
}

.active-game-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.active-game-text {
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--foreground);
}

.active-game-details {
    font-size: 0.8125rem;
    color: var(--muted-foreground);
}

/* Searching Overlay */
.searching-overlay,
.match-found-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
}

.searching-modal,
.match-found-modal {
    background-color: var(--card);
    border-radius: 1rem;
    padding: 2rem;
    max-width: 400px;
    width: 90%;
    text-align: center;
    border: 1px solid var(--border);
}

.searching-animation,
.match-found-animation {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.searching-modal h2,
.match-found-modal h2 {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0 0 0.5rem;
}

.search-info {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0 0 1rem;
}

.wait-time {
    font-size: 2rem;
    font-weight: 700;
    color: var(--foreground);
    font-variant-numeric: tabular-nums;
    margin: 0 0 1.5rem;
}

.opponent-info {
    font-size: 1rem;
    color: var(--muted-foreground);
    margin: 0.5rem 0 0;
}
</style>
