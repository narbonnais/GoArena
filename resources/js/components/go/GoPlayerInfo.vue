<script setup lang="ts">
import type { Stone } from '@/types/go';

const props = defineProps<{
    color: Stone;
    name: string;
    avatarUrl?: string;
    captures: number;
    isCurrentPlayer: boolean;
    isThinking?: boolean;
}>();
</script>

<template>
    <div class="player-card" :class="[color, { active: isCurrentPlayer }]">
        <!-- Stone/Avatar indicator -->
        <div class="player-icon">
            <template v-if="avatarUrl">
                <img :src="avatarUrl" :alt="name" class="avatar-img" />
            </template>
            <template v-else>
                <div class="stone" :class="color"></div>
            </template>
        </div>

        <!-- Player info -->
        <div class="player-details">
            <div class="player-name">{{ name }}</div>
            <div class="player-captures">
                <span class="captures-count">{{ captures }}</span>
                <span class="captures-label">captures</span>
            </div>
        </div>

        <!-- Status indicator -->
        <div class="status-area">
            <div v-if="isCurrentPlayer && !isThinking" class="turn-badge">
                YOUR TURN
            </div>
            <div v-else-if="isCurrentPlayer && isThinking" class="thinking-badge">
                <span class="thinking-dots">
                    <span class="dot"></span>
                    <span class="dot"></span>
                    <span class="dot"></span>
                </span>
                Thinking
            </div>
        </div>
    </div>
</template>

<style scoped>
.player-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    padding: 1.25rem;
    min-width: 140px;
    background-color: var(--card);
    border-radius: 0.75rem;
    border: 2px solid var(--border);
    transition: all 0.2s ease;
}

.player-card.active {
    border-color: var(--go-green);
    box-shadow: 0 0 20px var(--go-green-muted);
}

.player-card.black.active {
    border-color: #555;
    box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
}

.player-icon {
    display: flex;
    align-items: center;
    justify-content: center;
}

.avatar-img {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    object-fit: cover;
}

.stone {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
}

.stone.black {
    background: radial-gradient(circle at 30% 30%, #4a4a4a, #1a1a1a);
    border: 1px solid #000;
}

.stone.white {
    background: radial-gradient(circle at 30% 30%, #fff, #e0e0e0);
    border: 1px solid #bbb;
}

.player-details {
    text-align: center;
}

.player-name {
    font-size: 1rem;
    font-weight: 700;
    color: var(--foreground);
    margin-bottom: 0.25rem;
}

.player-captures {
    display: flex;
    align-items: baseline;
    justify-content: center;
    gap: 0.25rem;
}

.captures-count {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--foreground);
}

.captures-label {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.status-area {
    min-height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.turn-badge {
    padding: 0.375rem 0.75rem;
    background-color: var(--go-green);
    color: white;
    border-radius: 9999px;
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.player-card.black .turn-badge {
    background-color: #333;
}

.thinking-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.375rem 0.75rem;
    background-color: var(--muted);
    color: var(--muted-foreground);
    border-radius: 9999px;
    font-size: 0.625rem;
    font-weight: 600;
    letter-spacing: 0.05em;
}

.thinking-dots {
    display: inline-flex;
    gap: 2px;
}

.thinking-dots .dot {
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background-color: var(--muted-foreground);
    animation: bounce 1.4s infinite ease-in-out both;
}

.thinking-dots .dot:nth-child(1) {
    animation-delay: -0.32s;
}

.thinking-dots .dot:nth-child(2) {
    animation-delay: -0.16s;
}

@keyframes bounce {
    0%,
    80%,
    100% {
        transform: scale(0);
    }
    40% {
        transform: scale(1);
    }
}
</style>
