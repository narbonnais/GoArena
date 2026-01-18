<script setup lang="ts">
import type { GoBot } from '@/composables/go/useGoBots';

const props = defineProps<{
    bot: GoBot;
    selected: boolean;
}>();

const emit = defineEmits<{
    select: [botId: string];
}>();
</script>

<template>
    <button
        class="bot-card"
        :class="{ selected }"
        :style="{ '--bot-color': bot.color }"
        @click="emit('select', bot.id)"
    >
        <img :src="bot.avatarUrl" :alt="bot.name" class="bot-avatar" />
        <div class="bot-name">{{ bot.name }}</div>
        <div class="bot-personality">{{ bot.personality }}</div>
    </button>
</template>

<style scoped>
.bot-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 1rem 0.75rem;
    min-width: 110px;
    border-radius: 0.75rem;
    border: 2px solid var(--border);
    background-color: var(--card);
    cursor: pointer;
    transition: all 0.15s ease;
    flex-shrink: 0;
}

.bot-card:hover {
    border-color: var(--go-green);
    background-color: var(--accent);
}

.bot-card.selected {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
    box-shadow: 0 0 20px var(--go-green-muted);
}

.bot-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    margin-bottom: 0.375rem;
    object-fit: cover;
}

.bot-name {
    font-weight: 700;
    font-size: 0.875rem;
    color: var(--foreground);
}

.bot-personality {
    font-size: 0.625rem;
    color: var(--muted-foreground);
    text-align: center;
    margin-top: 0.25rem;
    max-width: 90px;
}
</style>
