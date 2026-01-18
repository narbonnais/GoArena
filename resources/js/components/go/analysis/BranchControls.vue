<script setup lang="ts">
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-vue-next';

defineProps<{
    canGoBack: boolean;
    canGoForward: boolean;
    disabled?: boolean;
    currentMoveNumber: number;
}>();

const emit = defineEmits<{
    (e: 'go-back'): void;
    (e: 'go-forward'): void;
    (e: 'go-start'): void;
    (e: 'go-end'): void;
}>();
</script>

<template>
    <div class="branch-controls">
        <!-- Navigation Row -->
        <div class="control-row navigation-row">
            <button
                class="control-btn with-shortcut"
                :disabled="!canGoBack || disabled"
                title="Go to start (Home)"
                @click="emit('go-start')"
            >
                <ChevronsLeft :size="18" />
                <span class="shortcut-hint">Home</span>
            </button>

            <button
                class="control-btn with-shortcut"
                :disabled="!canGoBack || disabled"
                title="Previous move (Left Arrow)"
                @click="emit('go-back')"
            >
                <ChevronLeft :size="18" />
                <span class="shortcut-hint">&larr;</span>
            </button>

            <span class="move-indicator">
                Move {{ currentMoveNumber }}
            </span>

            <button
                class="control-btn with-shortcut"
                :disabled="!canGoForward || disabled"
                title="Next move (Right Arrow)"
                @click="emit('go-forward')"
            >
                <ChevronRight :size="18" />
                <span class="shortcut-hint">&rarr;</span>
            </button>

            <button
                class="control-btn with-shortcut"
                :disabled="!canGoForward || disabled"
                title="Go to end (End)"
                @click="emit('go-end')"
            >
                <ChevronsRight :size="18" />
                <span class="shortcut-hint">End</span>
            </button>
        </div>

        <!-- Keyboard hint -->
        <div class="keyboard-hint">
            Press <kbd>?</kbd> for all shortcuts
        </div>
    </div>
</template>

<style scoped>
.branch-controls {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    align-items: center;
}

.control-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
}

.navigation-row {
    gap: 0.25rem;
}

.move-indicator {
    min-width: 5rem;
    text-align: center;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--foreground);
}

.control-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.125rem;
    padding: 0.5rem 0.625rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--foreground);
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.control-btn.with-shortcut {
    padding-bottom: 0.375rem;
}

.control-btn:hover:not(:disabled) {
    background-color: var(--accent);
    border-color: var(--foreground);
}

.control-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.shortcut-hint {
    font-size: 0.5625rem;
    font-weight: 500;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    opacity: 0.7;
}

.keyboard-hint {
    font-size: 0.6875rem;
    color: var(--muted-foreground);
    opacity: 0.6;
}

.keyboard-hint kbd {
    display: inline-block;
    padding: 0.125rem 0.375rem;
    font-size: 0.625rem;
    font-family: inherit;
    background-color: var(--muted);
    border: 1px solid var(--border);
    border-radius: 0.25rem;
    box-shadow: 0 1px 0 var(--border);
}
</style>
