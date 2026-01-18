<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { History, Eye } from 'lucide-vue-next';

// Mock data - in real app this would come from backend
const recentGames = [
    { id: 1, opponent: 'Kira', result: 'win', boardSize: 9, date: '2h ago', margin: 12.5 },
    { id: 2, opponent: 'Echo', result: 'loss', boardSize: 13, date: '5h ago', margin: 8.5 },
    { id: 3, opponent: 'Nova', result: 'win', boardSize: 9, date: '1d ago', margin: 15.5 },
    { id: 4, opponent: 'Kira', result: 'win', boardSize: 19, date: '2d ago', margin: 5.5 },
    { id: 5, opponent: 'Luna', result: 'loss', boardSize: 9, date: '3d ago', margin: 3.5 },
];
</script>

<template>
    <div class="recent-games-widget">
        <div class="widget-header">
            <History :size="18" class="header-icon" />
            <span class="header-title">Recent Games</span>
        </div>

        <div class="games-list">
            <div v-for="game in recentGames" :key="game.id" class="game-row">
                <span class="result-badge" :class="game.result">
                    {{ game.result === 'win' ? 'W' : 'L' }}
                </span>
                <div class="game-info">
                    <span class="opponent">vs {{ game.opponent }}</span>
                    <span class="meta">{{ game.boardSize }}x{{ game.boardSize }} &middot; {{ game.date }}</span>
                </div>
                <span class="margin" :class="game.result">
                    {{ game.result === 'win' ? '+' : '-' }}{{ game.margin }}
                </span>
                <Link :href="`/go/history/${game.id}`" class="review-btn">
                    <Eye :size="14" />
                </Link>
            </div>
        </div>

        <Link href="/go/history" class="view-all-link">
            View All Games
        </Link>
    </div>
</template>

<style scoped>
.recent-games-widget {
    padding: 1.25rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
}

.widget-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.header-icon {
    color: var(--go-green);
}

.header-title {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--foreground);
}

.games-list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.game-row {
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
    flex-shrink: 0;
}

.result-badge.win {
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.result-badge.loss {
    background-color: hsl(0 84% 60% / 0.15);
    color: hsl(0 84% 60%);
}

.game-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
    min-width: 0;
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

.margin {
    font-size: 0.75rem;
    font-weight: 600;
    width: 40px;
    text-align: right;
}

.margin.win {
    color: var(--go-green);
}

.margin.loss {
    color: hsl(0 84% 60%);
}

.review-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.375rem;
    color: var(--muted-foreground);
    text-decoration: none;
    transition: all 0.15s ease;
}

.review-btn:hover {
    color: var(--go-green);
    border-color: var(--go-green);
}

.view-all-link {
    display: block;
    text-align: center;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--go-green);
    margin-top: 1rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--border);
    text-decoration: none;
}

.view-all-link:hover {
    text-decoration: underline;
}
</style>
