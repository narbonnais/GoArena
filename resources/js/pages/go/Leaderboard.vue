<script setup lang="ts">

import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Crown, Medal, Trophy } from 'lucide-vue-next';
import { ref } from 'vue';

import type { LeaderboardEntry } from '@/types/multiplayer';

// Props from Inertia
const props = defineProps<{
    leaderboard: LeaderboardEntry[];
    boardSize: 9 | 13 | 19;
}>();

const selectedBoardSize = ref<9 | 13 | 19>(props.boardSize);

function changeBoardSize(size: 9 | 13 | 19) {
    selectedBoardSize.value = size;
    router.visit(`/leaderboard?board_size=${size}`, { preserveState: true });
}

function getRankIcon(rank: number) {
    if (rank === 1) return Crown;
    if (rank === 2) return Medal;
    if (rank === 3) return Trophy;
    return null;
}

function getRankClass(rank: number) {
    if (rank === 1) return 'gold';
    if (rank === 2) return 'silver';
    if (rank === 3) return 'bronze';
    return '';
}
</script>

<template>
    <Head title="Leaderboard" />

    <div class="leaderboard-page">
        <!-- Header -->
        <header class="page-header">
            <button class="back-btn" @click="router.visit('/go')">
                <ArrowLeft :size="18" />
                <span>Back</span>
            </button>
            <h1 class="page-title">
                <Trophy :size="24" />
                <span>Leaderboard</span>
            </h1>
            <div class="header-spacer"></div>
        </header>

        <!-- Board Size Tabs -->
        <nav class="board-tabs">
            <button
                v-for="size in [9, 13, 19] as const"
                :key="size"
                class="tab-btn"
                :class="{ active: selectedBoardSize === size }"
                @click="changeBoardSize(size)"
            >
                {{ size }}x{{ size }}
            </button>
        </nav>

        <!-- Leaderboard Table -->
        <main class="leaderboard-content">
            <div v-if="leaderboard.length === 0" class="empty-state">
                <p>No players ranked yet for {{ selectedBoardSize }}x{{ selectedBoardSize }}</p>
            </div>

            <div v-else class="leaderboard-table">
                <div class="table-header">
                    <span class="col-rank">Rank</span>
                    <span class="col-player">Player</span>
                    <span class="col-rating">Rating</span>
                    <span class="col-games">Games</span>
                    <span class="col-winrate">Win Rate</span>
                </div>

                <div
                    v-for="entry in leaderboard"
                    :key="entry.user.id"
                    class="table-row"
                    :class="getRankClass(entry.rank)"
                >
                    <span class="col-rank">
                        <component
                            :is="getRankIcon(entry.rank)"
                            v-if="getRankIcon(entry.rank)"
                            :size="18"
                            class="rank-icon"
                        />
                        <span v-else class="rank-number">{{ entry.rank }}</span>
                    </span>
                    <span class="col-player">
                        <span class="player-name">{{ entry.user.name }}</span>
                        <span class="rank-badge">{{ entry.rank_title }}</span>
                    </span>
                    <span class="col-rating">{{ entry.rating }}</span>
                    <span class="col-games">{{ entry.games_played }}</span>
                    <span class="col-winrate">{{ entry.win_rate }}%</span>
                </div>
            </div>
        </main>
    </div>
</template>

<style scoped>
.leaderboard-page {
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

.board-tabs {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem;
    background-color: var(--background-deep);
    border-bottom: 1px solid var(--border);
}

.tab-btn {
    padding: 0.5rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--muted-foreground);
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.tab-btn:hover {
    color: var(--foreground);
    border-color: var(--foreground);
}

.tab-btn.active {
    color: var(--go-green);
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.leaderboard-content {
    flex: 1;
    padding: 1rem;
    max-width: 800px;
    margin: 0 auto;
    width: 100%;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--muted-foreground);
}

.leaderboard-table {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.table-header {
    display: grid;
    grid-template-columns: 60px 1fr 80px 80px 80px;
    gap: 1rem;
    padding: 0.75rem 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.table-row {
    display: grid;
    grid-template-columns: 60px 1fr 80px 80px 80px;
    gap: 1rem;
    padding: 0.75rem 1rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    align-items: center;
}

.table-row.gold {
    background-color: hsl(45 100% 50% / 0.1);
    border-color: hsl(45 100% 50% / 0.3);
}

.table-row.silver {
    background-color: hsl(0 0% 75% / 0.1);
    border-color: hsl(0 0% 75% / 0.3);
}

.table-row.bronze {
    background-color: hsl(30 60% 50% / 0.1);
    border-color: hsl(30 60% 50% / 0.3);
}

.col-rank {
    display: flex;
    align-items: center;
    justify-content: center;
}

.rank-icon {
    color: var(--go-green);
}

.gold .rank-icon {
    color: hsl(45 100% 50%);
}

.silver .rank-icon {
    color: hsl(0 0% 75%);
}

.bronze .rank-icon {
    color: hsl(30 60% 50%);
}

.rank-number {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--muted-foreground);
}

.col-player {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.player-name {
    font-weight: 600;
    color: var(--foreground);
}

.rank-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.125rem 0.5rem;
    color: var(--go-green);
    background-color: var(--go-green-muted);
    border-radius: 9999px;
}

.col-rating {
    font-weight: 700;
    color: var(--foreground);
    text-align: center;
}

.col-games {
    text-align: center;
    color: var(--muted-foreground);
}

.col-winrate {
    text-align: center;
    color: var(--muted-foreground);
}

@media (max-width: 640px) {
    .table-header,
    .table-row {
        grid-template-columns: 50px 1fr 60px;
    }

    .col-games,
    .col-winrate {
        display: none;
    }
}
</style>
