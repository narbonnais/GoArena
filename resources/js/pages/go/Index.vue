<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { History, Play, Loader2, Bot, Users, ChevronDown } from 'lucide-vue-next';
import { ref, computed } from 'vue';

import GoBotCard from '@/components/go/GoBotCard.vue';
import PlaySidebar from '@/components/go/PlaySidebar.vue';
import TimeControlSelector from '@/components/go/TimeControlSelector.vue';
import { useGoBots } from '@/composables/go/useGoBots';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type AppPageProps } from '@/types';

interface RecentGame {
    id: number;
    opponent: string;
    result: 'win' | 'loss' | 'draw';
    boardSize: number;
    date: string;
}

const props = defineProps<{
    totalUsers: number;
    recentGames: RecentGame[];
}>();

const page = usePage<AppPageProps>();
const isAuthenticated = !!page.props.auth?.user;

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Play',
        href: '/go',
    },
];

const { bots, selectedBot, selectedBotId, selectBot, boardSize, setBoardSize } = useGoBots();
const timeControl = ref('blitz');
const isSearching = ref(false);
const gameMode = ref<'bot' | 'player'>('player');

// Mobile sidebar collapse states
const showMobileStats = ref(false);
const showMobileRecent = ref(false);

const boardSizes = [
    { value: 9 as const, label: '9×9', description: 'Quick game' },
    { value: 13 as const, label: '13×13', description: 'Medium' },
    { value: 19 as const, label: '19×19', description: 'Traditional' },
];

// Get time in seconds for the selected time control
const timeControls: Record<string, number> = {
    bullet: 60,
    blitz: 300,
    rapid: 900,
    classical: 1800,
};

// Play button label
const playButtonLabel = computed(() => {
    if (isSearching.value) return 'SEARCHING...';
    return gameMode.value === 'player' ? 'FIND MATCH' : 'PLAY';
});

function startGame() {
    isSearching.value = true;

    if (gameMode.value === 'player') {
        // Go to multiplayer lobby
        router.visit('/go/multiplayer');
    } else {
        // Simulate matchmaking delay for bot game
        setTimeout(() => {
            router.visit('/go/play', {
                data: {
                    boardSize: boardSize.value,
                    botId: selectedBot.value.id,
                    timeControl: timeControls[timeControl.value],
                },
            });
        }, 800);
    }
}
</script>

<template>
    <Head title="Play Go" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="index-layout">
            <!-- Main Setup Column -->
            <div class="setup-column">
                <!-- Giant Play Button -->
                <button class="play-button" :class="{ searching: isSearching }" :disabled="isSearching" @click="startGame">
                    <Loader2 v-if="isSearching" class="play-icon spinning" :size="32" />
                    <Play v-else class="play-icon" :size="32" />
                    <span class="play-text">{{ playButtonLabel }}</span>
                </button>

                <!-- Game Mode Selection -->
                <section class="setup-section">
                    <h2 class="section-title">Game Mode</h2>
                    <div class="mode-options">
                        <button
                            class="mode-card"
                            :class="{ selected: gameMode === 'player' }"
                            @click="gameMode = 'player'"
                        >
                            <Users :size="24" class="mode-icon" />
                            <span class="mode-name">vs Player</span>
                            <span class="mode-desc">Ranked matchmaking</span>
                        </button>
                        <button
                            class="mode-card"
                            :class="{ selected: gameMode === 'bot' }"
                            @click="gameMode = 'bot'"
                        >
                            <Bot :size="24" class="mode-icon" />
                            <span class="mode-name">vs Bot</span>
                            <span class="mode-desc">Practice against AI</span>
                        </button>
                    </div>
                </section>

                <!-- Board Size Selection (only for bot games) -->
                <section v-if="gameMode === 'bot'" class="setup-section">
                    <h2 class="section-title">Board Size</h2>
                    <div class="board-sizes">
                        <button
                            v-for="size in boardSizes"
                            :key="size.value"
                            class="size-card"
                            :class="{ selected: boardSize === size.value }"
                            @click="setBoardSize(size.value)"
                        >
                            <span class="size-value">{{ size.label }}</span>
                            <span class="size-desc">{{ size.description }}</span>
                        </button>
                    </div>
                </section>

                <!-- Time Control Selection (only for bot games) -->
                <section v-if="gameMode === 'bot'" class="setup-section">
                    <TimeControlSelector v-model="timeControl" />
                </section>

                <!-- Opponent Selection (only for bot games) -->
                <section v-if="gameMode === 'bot'" class="setup-section">
                    <h2 class="section-title">Choose Your Opponent</h2>
                    <div class="bot-carousel">
                        <div class="bot-track">
                            <GoBotCard
                                v-for="bot in bots"
                                :key="bot.id"
                                :bot="bot"
                                :selected="selectedBotId === bot.id"
                                @select="selectBot"
                            />
                        </div>
                    </div>
                </section>

                <!-- Match Preview (only for bot games) -->
                <div v-if="gameMode === 'bot'" class="match-preview">
                    <div class="match-player you">
                        <div class="match-stone black"></div>
                        <div class="match-info">
                            <span class="match-name">You</span>
                            <span class="match-role">Black - First Move</span>
                        </div>
                    </div>
                    <div class="match-vs">VS</div>
                    <div class="match-player opponent">
                        <img :src="selectedBot.avatarUrl" :alt="selectedBot.name" class="match-avatar" />
                        <div class="match-info">
                            <span class="match-name">{{ selectedBot.name }}</span>
                            <span class="match-personality">{{ selectedBot.personality }}</span>
                        </div>
                    </div>
                </div>

                <!-- Multiplayer Info (only for player games) -->
                <section v-if="gameMode === 'player'" class="setup-section">
                    <div class="multiplayer-info">
                        <Users :size="32" class="mp-icon" />
                        <p class="mp-text">Find a real opponent with similar skill level</p>
                        <p class="mp-subtext">Choose board size and time control in the next screen</p>
                    </div>
                </section>

                <!-- History Link -->
                <Link
                    v-if="isAuthenticated"
                    href="/go/history"
                    class="history-link"
                >
                    <History :size="16" />
                    View Game History
                </Link>

                <!-- Mobile Collapsible Cards (shown below main content on mobile) -->
                <div class="mobile-sidebar-cards">
                    <!-- Stats Card -->
                    <div class="collapsible-card">
                        <button class="collapsible-header" @click="showMobileStats = !showMobileStats">
                            <Users :size="18" class="header-icon" />
                            <span>{{ props.totalUsers }} {{ props.totalUsers === 1 ? 'player' : 'players' }} registered</span>
                            <ChevronDown :size="18" class="chevron" :class="{ open: showMobileStats }" />
                        </button>
                    </div>

                    <!-- Recent Games Card -->
                    <div v-if="props.recentGames.length > 0" class="collapsible-card">
                        <button class="collapsible-header" @click="showMobileRecent = !showMobileRecent">
                            <History :size="18" class="header-icon" />
                            <span>Recent Games</span>
                            <ChevronDown :size="18" class="chevron" :class="{ open: showMobileRecent }" />
                        </button>
                        <div v-if="showMobileRecent" class="collapsible-content">
                            <div v-for="game in props.recentGames" :key="game.id" class="mobile-game-item">
                                <span class="result-badge" :class="game.result">
                                    {{ game.result === 'win' ? 'W' : game.result === 'loss' ? 'L' : 'D' }}
                                </span>
                                <div class="game-info">
                                    <span class="opponent">vs {{ game.opponent }}</span>
                                    <span class="meta">{{ game.boardSize }}x{{ game.boardSize }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Rail (Desktop only) -->
            <aside class="right-rail">
                <PlaySidebar :total-users="props.totalUsers" :recent-games="props.recentGames" />
            </aside>
        </div>

        <!-- Mobile Sticky Play Button -->
        <div class="mobile-sticky-play">
            <button class="mobile-play-button" :class="{ searching: isSearching }" :disabled="isSearching" @click="startGame">
                <Loader2 v-if="isSearching" class="play-icon spinning" :size="24" />
                <Play v-else class="play-icon" :size="24" />
                <span>{{ playButtonLabel }}</span>
            </button>
        </div>
    </AppLayout>
</template>

<style scoped>
/* CSS Grid Layout - Chess.com style */
.index-layout {
    display: grid;
    grid-template-columns: 1fr;
    gap: 2rem;
    padding: 2rem 1rem;
    padding-bottom: 100px; /* Space for mobile sticky button */
    max-width: 1200px;
    margin: 0 auto;
}

@media (min-width: 1024px) {
    .index-layout {
        grid-template-columns: minmax(0, 600px) 280px;
        justify-content: center;
        padding-bottom: 2rem;
    }
}

/* Right Rail - Desktop Only */
.right-rail {
    display: none;
}

@media (min-width: 1024px) {
    .right-rail {
        display: block;
        position: sticky;
        top: 1rem;
        align-self: start;
    }
}

/* Setup Column */
.setup-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2rem;
    width: 100%;
}

/* Giant Play Button */
.play-button {
    width: 100%;
    max-width: 400px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    padding: 1.25rem 2rem;
    font-size: 1.5rem;
    font-weight: 800;
    letter-spacing: 0.1em;
    color: white;
    background: linear-gradient(135deg, var(--go-green) 0%, var(--go-green-hover) 100%);
    border: none;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 4px 20px var(--go-green-muted);
}

.play-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 30px var(--go-green-muted);
}

.play-button:active {
    transform: translateY(0);
}

.play-button.searching {
    background: linear-gradient(135deg, var(--go-green-hover) 0%, var(--go-green) 100%);
    cursor: wait;
}

.play-button:disabled {
    transform: none;
}

.play-icon {
    fill: white;
}

.play-icon.spinning {
    fill: none;
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

.play-text {
    font-family: inherit;
}

/* Sections */
.setup-section {
    width: 100%;
}

.section-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin: 0 0 1rem;
    text-align: center;
}

/* Board Sizes */
.board-sizes {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.75rem;
}

.size-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 1rem 0.75rem;
    background-color: var(--card);
    border: 2px solid var(--border);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.size-card:hover {
    border-color: var(--go-green);
}

.size-card.selected {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.size-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
}

.size-card.selected .size-value {
    color: var(--go-green);
}

.size-desc {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

/* Game Mode Selection */
.mode-options {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
}

.mode-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 1.25rem 1rem;
    background-color: var(--card);
    border: 2px solid var(--border);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.mode-card:hover {
    border-color: var(--go-green);
}

.mode-card.selected {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.mode-icon {
    color: var(--muted-foreground);
}

.mode-card.selected .mode-icon {
    color: var(--go-green);
}

.mode-name {
    font-size: 1rem;
    font-weight: 700;
    color: var(--foreground);
}

.mode-card.selected .mode-name {
    color: var(--go-green);
}

.mode-desc {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

/* Multiplayer Info */
.multiplayer-info {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    padding: 2rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    width: 100%;
    text-align: center;
}

.mp-icon {
    color: var(--go-green);
}

.mp-text {
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0;
}

.mp-subtext {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0;
}

/* Bot Carousel */
.bot-carousel {
    width: 100%;
    overflow-x: auto;
    padding-bottom: 0.5rem;
    -webkit-overflow-scrolling: touch;
}

.bot-track {
    display: flex;
    gap: 0.75rem;
    padding: 0.25rem;
    min-width: min-content;
}

/* Scrollbar styling */
.bot-carousel::-webkit-scrollbar {
    height: 6px;
}

.bot-carousel::-webkit-scrollbar-track {
    background: var(--background);
    border-radius: 3px;
}

.bot-carousel::-webkit-scrollbar-thumb {
    background-color: var(--border);
    border-radius: 3px;
}

/* Match Preview */
.match-preview {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1.5rem;
    padding: 1.25rem 1.5rem;
    background-color: var(--card);
    border-radius: 0.75rem;
    border: 1px solid var(--border);
    width: 100%;
}

.match-player {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.match-stone {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.match-stone.black {
    background: radial-gradient(circle at 30% 30%, #4a4a4a, #1a1a1a);
    border: 1px solid #000;
}

.match-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    object-fit: cover;
}

.match-info {
    display: flex;
    flex-direction: column;
}

.match-name {
    font-weight: 700;
    color: var(--foreground);
    font-size: 0.9375rem;
}

.match-role {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.match-personality {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.match-vs {
    font-size: 0.875rem;
    font-weight: 700;
    color: var(--muted-foreground);
    padding: 0.5rem 0.75rem;
    background-color: var(--background);
    border-radius: 0.5rem;
}

/* History Link */
.history-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--muted-foreground);
    text-decoration: none;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: all 0.15s ease;
}

.history-link:hover {
    color: var(--go-green);
    background-color: var(--go-green-muted);
}

/* Responsive */
@media (max-width: 480px) {
    .match-preview {
        flex-direction: column;
        gap: 1rem;
    }

    .match-vs {
        order: 0;
    }
}

/* Mobile Sidebar Cards (collapsible) */
.mobile-sidebar-cards {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    width: 100%;
    margin-top: 1rem;
}

@media (min-width: 1024px) {
    .mobile-sidebar-cards {
        display: none;
    }
}

.collapsible-card {
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    overflow: hidden;
}

.collapsible-header {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    width: 100%;
    padding: 1rem;
    background: transparent;
    border: none;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--foreground);
    cursor: pointer;
    text-align: left;
}

.collapsible-header .header-icon {
    color: var(--go-green);
    flex-shrink: 0;
}

.collapsible-header .chevron {
    margin-left: auto;
    color: var(--muted-foreground);
    transition: transform 0.2s ease;
}

.collapsible-header .chevron.open {
    transform: rotate(180deg);
}

.collapsible-content {
    padding: 0 1rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.mobile-game-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    background-color: var(--background);
    border-radius: 0.5rem;
}

.mobile-game-item .game-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.mobile-game-item .opponent {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--foreground);
}

.mobile-game-item .meta {
    font-size: 0.6875rem;
    color: var(--muted-foreground);
}

.result-badge {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.6875rem;
    font-weight: 700;
    border-radius: 0.25rem;
    flex-shrink: 0;
}

.result-badge.win {
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.result-badge.loss {
    background-color: rgba(224, 91, 91, 0.15);
    color: var(--danger);
}

.result-badge.draw {
    background-color: hsl(45 100% 50% / 0.15);
    color: hsl(45 100% 40%);
}

/* Mobile Sticky Play Button */
.mobile-sticky-play {
    display: block;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    padding: 1rem;
    padding-bottom: calc(1rem + env(safe-area-inset-bottom, 0));
    background: linear-gradient(to top, var(--background) 80%, transparent);
    z-index: 50;
}

@media (min-width: 1024px) {
    .mobile-sticky-play {
        display: none;
    }
}

.mobile-play-button {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 1rem;
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.05em;
    color: white;
    background: linear-gradient(135deg, var(--accent-play) 0%, #3dd97a 100%);
    border: none;
    border-radius: 0.75rem;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(47, 195, 107, 0.3);
}

.mobile-play-button .play-icon {
    fill: white;
}

.mobile-play-button.searching {
    background: linear-gradient(135deg, #3dd97a 0%, var(--accent-play) 100%);
    cursor: wait;
}

.mobile-play-button .spinning {
    fill: none;
    animation: spin 1s linear infinite;
}

/* Hide desktop play button on mobile (use sticky instead) */
@media (max-width: 1023px) {
    .play-button {
        display: none;
    }
}
</style>
