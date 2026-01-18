<script setup lang="ts">
import { Gamepad2, TrendingUp, Award } from 'lucide-vue-next';

// Mock stats - in real app this would come from backend
const stats = {
    totalGames: 42,
    winRate: 67,
    currentRank: '15k',
};
</script>

<template>
    <div class="player-stats">
        <div class="stat-card">
            <div class="stat-icon games">
                <Gamepad2 :size="20" />
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ stats.totalGames }}</span>
                <span class="stat-label">Total Games</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon winrate">
                <TrendingUp :size="20" />
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ stats.winRate }}%</span>
                <span class="stat-label">Win Rate</span>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon rank">
                <Award :size="20" />
            </div>
            <div class="stat-info">
                <span class="stat-value">{{ stats.currentRank }}</span>
                <span class="stat-label">Current Rank</span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.player-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
}

.stat-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 0.5rem;
    flex-shrink: 0;
}

.stat-icon.games {
    background-color: hsl(220 90% 56% / 0.15);
    color: hsl(220 90% 56%);
}

.stat-icon.winrate {
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.stat-icon.rank {
    background-color: hsl(45 100% 50% / 0.15);
    color: hsl(45 100% 40%);
}

.stat-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
}

.stat-label {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}
</style>
