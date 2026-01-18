<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Users, History, Gamepad2, Swords, Bot } from 'lucide-vue-next';

import DailyPuzzle from '@/components/go/learn/DailyPuzzle.vue';

interface RecentGame {
    id: number;
    opponent: string;
    result: 'win' | 'loss' | 'draw';
    boardSize: number;
    date: string;
}

defineProps<{
    totalUsers: number;
    recentGames: RecentGame[];
}>();

const alternativeModes = [
    { id: 'puzzle', name: 'Puzzles', icon: Gamepad2, coming: true },
    { id: 'ranked', name: 'Ranked', icon: Swords, coming: true },
    { id: 'custom', name: 'Custom', icon: Bot, coming: true },
];
</script>

<template>
    <div class="play-sidebar">
        <!-- Registered Users -->
        <div class="sidebar-card online-players">
            <div class="card-header">
                <Users :size="18" class="header-icon" />
                <span class="header-label">Registered Users</span>
            </div>
            <div class="player-count">{{ totalUsers }} {{ totalUsers === 1 ? 'player' : 'players' }}</div>
        </div>

        <!-- Recent Games -->
        <div v-if="recentGames.length > 0" class="sidebar-card recent-games">
            <div class="card-header">
                <History :size="18" class="header-icon" />
                <span class="header-label">Recent Games</span>
            </div>
            <div class="games-list">
                <div v-for="game in recentGames" :key="game.id" class="game-item">
                    <span class="result-badge" :class="game.result">
                        {{ game.result === 'win' ? 'W' : game.result === 'loss' ? 'L' : 'D' }}
                    </span>
                    <div class="game-info">
                        <span class="opponent">vs {{ game.opponent }}</span>
                        <span class="meta">{{ game.boardSize }}x{{ game.boardSize }} &middot; {{ game.date }}</span>
                    </div>
                </div>
            </div>
            <Link href="/go/history" class="view-all-link">View All</Link>
        </div>

        <!-- Daily Puzzle -->
        <DailyPuzzle />

        <!-- Alternative Modes -->
        <div class="sidebar-card alt-modes">
            <div class="card-header">
                <Gamepad2 :size="18" class="header-icon" />
                <span class="header-label">More Ways to Play</span>
            </div>
            <div class="modes-list">
                <button
                    v-for="mode in alternativeModes"
                    :key="mode.id"
                    class="mode-item"
                    :disabled="mode.coming"
                >
                    <component :is="mode.icon" :size="16" />
                    <span>{{ mode.name }}</span>
                    <span v-if="mode.coming" class="coming-badge">Soon</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.play-sidebar {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 260px;
}

.sidebar-card {
    padding: 1rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
}

.card-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.header-icon {
    color: var(--go-green);
}

.header-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

/* Online Players */
.player-count {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--foreground);
}

/* Recent Games */
.games-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.game-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem;
    background-color: var(--background);
    border-radius: 0.5rem;
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
}

.result-badge.win {
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.result-badge.loss {
    background-color: hsl(0 84% 60% / 0.15);
    color: hsl(0 84% 60%);
}

.result-badge.draw {
    background-color: hsl(45 100% 50% / 0.15);
    color: hsl(45 100% 40%);
}

.game-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.opponent {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--foreground);
}

.meta {
    font-size: 0.6875rem;
    color: var(--muted-foreground);
}

.view-all-link {
    display: block;
    text-align: center;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--go-green);
    margin-top: 0.75rem;
    text-decoration: none;
}

.view-all-link:hover {
    text-decoration: underline;
}

/* Alternative Modes */
.modes-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.mode-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.625rem 0.75rem;
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--foreground);
    cursor: pointer;
    transition: all 0.15s ease;
}

.mode-item:hover:not(:disabled) {
    border-color: var(--go-green);
}

.mode-item:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.coming-badge {
    margin-left: auto;
    font-size: 0.625rem;
    font-weight: 600;
    color: var(--muted-foreground);
    background-color: var(--border);
    padding: 0.125rem 0.375rem;
    border-radius: 0.25rem;
    text-transform: uppercase;
}
</style>
