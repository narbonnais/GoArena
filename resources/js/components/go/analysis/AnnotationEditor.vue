<script setup lang="ts">
import { ref, watch } from 'vue';

import type { MoveSymbol } from '@/types/analysis';

const props = defineProps<{
    comment: string | null;
    symbols: MoveSymbol[];
    disabled?: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:comment', comment: string | null): void;
    (e: 'toggle-symbol', symbol: MoveSymbol): void;
}>();

// Local state for comment editing
const localComment = ref(props.comment ?? '');

// Sync with prop changes
watch(
    () => props.comment,
    (newComment) => {
        localComment.value = newComment ?? '';
    }
);

// Save comment on blur
function handleBlur() {
    const trimmed = localComment.value.trim();
    emit('update:comment', trimmed || null);
}

// Symbol definitions
interface SymbolDef {
    value: MoveSymbol;
    display: string;
    label: string;
    color: string;
}

const symbolDefinitions: SymbolDef[] = [
    { value: 'good', display: '!', label: 'Good move', color: '#22c55e' },
    { value: 'bad', display: '?', label: 'Bad move', color: '#f59e0b' },
    { value: 'blunder', display: '??', label: 'Blunder', color: '#ef4444' },
];

function isSymbolActive(symbol: MoveSymbol): boolean {
    return props.symbols.includes(symbol);
}

function handleSymbolClick(symbol: MoveSymbol) {
    if (props.disabled) return;
    emit('toggle-symbol', symbol);
}
</script>

<template>
    <div class="annotation-editor">
        <div class="editor-header">
            <span class="header-title">Annotations</span>
        </div>

        <!-- Symbol Buttons -->
        <div class="symbol-buttons">
            <button
                v-for="sym in symbolDefinitions"
                :key="sym.value"
                class="symbol-btn"
                :class="{ active: isSymbolActive(sym.value) }"
                :style="{
                    '--symbol-color': sym.color,
                }"
                :disabled="disabled"
                :title="sym.label"
                @click="handleSymbolClick(sym.value)"
            >
                {{ sym.display }}
            </button>
        </div>

        <!-- Comment Textarea -->
        <div class="comment-section">
            <textarea
                v-model="localComment"
                class="comment-input"
                placeholder="Add a comment for this move..."
                :disabled="disabled"
                rows="2"
                @blur="handleBlur"
            ></textarea>
        </div>
    </div>
</template>

<style scoped>
.annotation-editor {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    padding: 0.75rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
}

.editor-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.symbol-buttons {
    display: flex;
    gap: 0.25rem;
    flex-wrap: wrap;
}

.symbol-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    min-width: 2rem;
    height: 2rem;
    padding: 0 0.375rem;
    font-size: 0.8125rem;
    font-weight: 700;
    color: var(--muted-foreground);
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.symbol-btn:hover:not(:disabled) {
    color: var(--symbol-color);
    border-color: var(--symbol-color);
    background-color: color-mix(in srgb, var(--symbol-color) 10%, transparent);
}

.symbol-btn.active {
    color: white;
    background-color: var(--symbol-color);
    border-color: var(--symbol-color);
}

.symbol-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.comment-section {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.comment-input {
    width: 100%;
    padding: 0.5rem;
    font-size: 0.8125rem;
    font-family: inherit;
    color: var(--foreground);
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.375rem;
    resize: vertical;
    min-height: 3rem;
    transition: border-color 0.15s ease;
}

.comment-input:focus {
    outline: none;
    border-color: var(--ring);
}

.comment-input:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.comment-input::placeholder {
    color: var(--muted-foreground);
}
</style>
