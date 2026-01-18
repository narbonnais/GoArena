<script setup lang="ts">
import { Trophy, Star, Target, Zap, Crown, Shield, Medal, Award } from 'lucide-vue-next';
import { computed } from 'vue';

interface Achievement {
    id: string;
    name: string;
    description: string;
    icon: typeof Trophy;
    unlocked: boolean;
    progress?: number;
    maxProgress?: number;
}

// Mock achievements - in real app this would come from backend
const achievements: Achievement[] = [
    {
        id: 'first-win',
        name: 'First Victory',
        description: 'Win your first game',
        icon: Trophy,
        unlocked: true,
    },
    {
        id: '10-games',
        name: 'Getting Started',
        description: 'Play 10 games',
        icon: Star,
        unlocked: true,
        progress: 10,
        maxProgress: 10,
    },
    {
        id: 'beat-kira',
        name: 'Bot Slayer',
        description: 'Defeat Kira',
        icon: Target,
        unlocked: true,
    },
    {
        id: '19x19-victor',
        name: '19x19 Victor',
        description: 'Win a game on 19x19 board',
        icon: Crown,
        unlocked: true,
    },
    {
        id: 'win-streak-5',
        name: 'On Fire',
        description: 'Win 5 games in a row',
        icon: Zap,
        unlocked: false,
        progress: 3,
        maxProgress: 5,
    },
    {
        id: 'beat-all-bots',
        name: 'Bot Master',
        description: 'Defeat all AI opponents',
        icon: Shield,
        unlocked: false,
        progress: 2,
        maxProgress: 4,
    },
    {
        id: '50-games',
        name: 'Dedicated Player',
        description: 'Play 50 games',
        icon: Medal,
        unlocked: false,
        progress: 42,
        maxProgress: 50,
    },
    {
        id: 'perfect-game',
        name: 'Flawless',
        description: 'Win by 50+ points',
        icon: Award,
        unlocked: false,
    },
];

const unlockedCount = computed(() => achievements.filter(a => a.unlocked).length);
</script>

<template>
    <div class="achievements">
        <div class="achievements-header">
            <Trophy :size="18" class="header-icon" />
            <span class="header-title">Achievements</span>
            <span class="achievement-count">{{ unlockedCount }}/{{ achievements.length }}</span>
        </div>

        <div class="achievements-grid">
            <div
                v-for="achievement in achievements"
                :key="achievement.id"
                class="achievement-card"
                :class="{ locked: !achievement.unlocked }"
            >
                <div class="achievement-icon">
                    <component :is="achievement.icon" :size="20" />
                </div>
                <div class="achievement-info">
                    <span class="achievement-name">{{ achievement.name }}</span>
                    <span class="achievement-desc">{{ achievement.description }}</span>
                    <div v-if="achievement.progress !== undefined && !achievement.unlocked" class="progress-bar">
                        <div
                            class="progress-fill"
                            :style="{ width: `${(achievement.progress / (achievement.maxProgress ?? 1)) * 100}%` }"
                        ></div>
                        <span class="progress-text">{{ achievement.progress }}/{{ achievement.maxProgress }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.achievements {
    padding: 1.25rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
}

.achievements-header {
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

.achievement-count {
    margin-left: auto;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--muted-foreground);
    background-color: var(--background);
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.achievements-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 0.75rem;
}

.achievement-card {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 0.75rem;
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    transition: all 0.15s ease;
}

.achievement-card.locked {
    opacity: 0.5;
}

.achievement-card:not(.locked) {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.achievement-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background-color: var(--card);
    border-radius: 0.5rem;
    flex-shrink: 0;
    color: var(--muted-foreground);
}

.achievement-card:not(.locked) .achievement-icon {
    background-color: var(--go-green);
    color: white;
}

.achievement-info {
    flex: 1;
    min-width: 0;
}

.achievement-name {
    display: block;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--foreground);
}

.achievement-desc {
    display: block;
    font-size: 0.6875rem;
    color: var(--muted-foreground);
    margin-top: 0.125rem;
}

.progress-bar {
    position: relative;
    height: 12px;
    background-color: var(--card);
    border-radius: 6px;
    margin-top: 0.5rem;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background-color: var(--go-green);
    border-radius: 6px;
    transition: width 0.3s ease;
}

.progress-text {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.5625rem;
    font-weight: 600;
    color: var(--foreground);
}
</style>
