<script setup lang="ts">
import { Flag, SkipForward, Undo2 } from 'lucide-vue-next';

const props = defineProps<{
    disabled?: boolean;
    isGameOver?: boolean;
    canUndo?: boolean;
}>();

const emit = defineEmits<{
    (e: 'pass'): void;
    (e: 'resign'): void;
    (e: 'undo'): void;
}>();
</script>

<template>
    <div class="game-controls">
        <template v-if="!isGameOver">
            <button class="control-btn pass-btn" :disabled="disabled" @click="emit('pass')">
                <SkipForward class="btn-icon" :size="18" />
                Pass
            </button>
            <button class="control-btn undo-btn" :disabled="disabled || !canUndo" @click="emit('undo')">
                <Undo2 class="btn-icon" :size="18" />
                Undo
            </button>
            <button class="control-btn resign-btn" :disabled="disabled" @click="emit('resign')">
                <Flag class="btn-icon" :size="18" />
                Resign
            </button>
        </template>
    </div>
</template>

<style scoped>
.game-controls {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}

.control-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.15s ease;
    border: 1px solid var(--border);
    background-color: var(--card);
    color: var(--foreground);
}

.control-btn:hover:not(:disabled) {
    background-color: var(--accent);
}

.control-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.pass-btn {
    background-color: var(--secondary);
    border-color: var(--border);
}

.pass-btn:hover:not(:disabled) {
    background-color: var(--accent);
}

.undo-btn {
    background-color: var(--background);
    border-color: var(--border);
    color: var(--foreground);
}

.undo-btn:hover:not(:disabled) {
    background-color: var(--accent);
    border-color: var(--go-green);
}

.resign-btn {
    background-color: hsl(0 84% 60% / 0.15);
    border-color: hsl(0 84% 60% / 0.3);
    color: hsl(0 84% 65%);
}

.resign-btn:hover:not(:disabled) {
    background-color: hsl(0 84% 60% / 0.25);
}

.btn-icon {
    flex-shrink: 0;
}
</style>
