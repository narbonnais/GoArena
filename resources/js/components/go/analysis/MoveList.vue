<script setup lang="ts">
import { MessageSquare, GitBranch } from 'lucide-vue-next';
import { computed } from 'vue';

import type { MoveNode, MoveSymbol } from '@/types/analysis';
import type { Coordinate } from '@/types/go';

const props = defineProps<{
    nodes: Record<string, MoveNode>;
    mainLine: MoveNode[];
    currentNodeId: string;
    boardSize: number;
}>();

const emit = defineEmits<{
    (e: 'select-move', nodeId: string): void;
}>();

// Convert coordinate to Go notation (A1, B2, etc.)
function coordToNotation(coord: Coordinate | null, boardSize: number): string {
    if (!coord) return 'Pass';

    // Letters skip 'I' in Go notation
    const letters = 'ABCDEFGHJKLMNOPQRST';
    const col = letters[coord.x];
    const row = boardSize - coord.y;

    return `${col}${row}`;
}

// Get symbol display
const SYMBOL_DISPLAY: Record<MoveSymbol, string> = {
    good: '!',
    bad: '?',
    interesting: '!?',
    doubtful: '?!',
    brilliant: '!!',
    blunder: '??',
};

function getSymbolsDisplay(symbols: MoveSymbol[]): string {
    return symbols.map((s) => SYMBOL_DISPLAY[s]).join('');
}

// Group moves into pairs (1. B... W... 2. B... W...)
interface MovePair {
    moveNumber: number;
    black: MoveNode | null;
    white: MoveNode | null;
}

const movePairs = computed<MovePair[]>(() => {
    const pairs: MovePair[] = [];
    let currentPair: MovePair | null = null;

    for (const node of props.mainLine) {
        const pairNumber = Math.ceil(node.moveNumber / 2);

        if (!currentPair || currentPair.moveNumber !== pairNumber) {
            currentPair = { moveNumber: pairNumber, black: null, white: null };
            pairs.push(currentPair);
        }

        if (node.stone === 'black') {
            currentPair.black = node;
        } else {
            currentPair.white = node;
        }
    }

    return pairs;
});

function hasAnnotations(node: MoveNode | null): boolean {
    if (!node) return false;
    return !!node.comment || node.symbols.length > 0;
}

function hasVariations(node: MoveNode | null): boolean {
    if (!node) return false;
    const parent = node.parent ? props.nodes[node.parent] : null;
    return parent ? parent.children.length > 1 : false;
}
</script>

<template>
    <div class="move-list">
        <div class="move-list-header">
            <span class="header-title">Moves</span>
            <span class="move-count">{{ mainLine.length }} moves</span>
        </div>

        <div class="move-list-content">
            <!-- Empty state -->
            <div v-if="mainLine.length === 0" class="empty-state">
                Play moves on the board to begin
            </div>

            <!-- Move pairs -->
            <div
                v-for="pair in movePairs"
                :key="pair.moveNumber"
                class="move-pair"
            >
                <span class="move-number">{{ pair.moveNumber }}.</span>

                <!-- Black's move -->
                <button
                    v-if="pair.black"
                    class="move-button"
                    :class="{
                        'is-current': pair.black.id === currentNodeId,
                        'has-comment': pair.black.comment,
                        'has-variation': hasVariations(pair.black),
                    }"
                    @click="emit('select-move', pair.black.id)"
                >
                    <span class="stone-indicator black"></span>
                    <span class="move-notation">
                        {{ coordToNotation(pair.black.coordinate, boardSize) }}
                    </span>
                    <span v-if="pair.black.symbols.length > 0" class="move-symbols">
                        {{ getSymbolsDisplay(pair.black.symbols) }}
                    </span>
                    <MessageSquare
                        v-if="pair.black.comment"
                        :size="12"
                        class="annotation-icon"
                    />
                    <GitBranch
                        v-if="hasVariations(pair.black)"
                        :size="12"
                        class="branch-icon"
                    />
                </button>
                <span v-else class="move-placeholder">...</span>

                <!-- White's move -->
                <button
                    v-if="pair.white"
                    class="move-button"
                    :class="{
                        'is-current': pair.white.id === currentNodeId,
                        'has-comment': pair.white.comment,
                        'has-variation': hasVariations(pair.white),
                    }"
                    @click="emit('select-move', pair.white.id)"
                >
                    <span class="stone-indicator white"></span>
                    <span class="move-notation">
                        {{ coordToNotation(pair.white.coordinate, boardSize) }}
                    </span>
                    <span v-if="pair.white.symbols.length > 0" class="move-symbols">
                        {{ getSymbolsDisplay(pair.white.symbols) }}
                    </span>
                    <MessageSquare
                        v-if="pair.white.comment"
                        :size="12"
                        class="annotation-icon"
                    />
                    <GitBranch
                        v-if="hasVariations(pair.white)"
                        :size="12"
                        class="branch-icon"
                    />
                </button>
                <span v-else class="move-placeholder"></span>
            </div>
        </div>
    </div>
</template>

<style scoped>
.move-list {
    display: flex;
    flex-direction: column;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    overflow: hidden;
}

.move-list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border);
    background-color: var(--background);
}

.header-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--foreground);
}

.move-count {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.move-list-content {
    padding: 0.5rem;
    max-height: 300px;
    overflow-y: auto;
}

.empty-state {
    padding: 1.5rem 1rem;
    text-align: center;
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.move-pair {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.125rem 0;
}

.move-number {
    min-width: 1.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-align: right;
}

.move-button {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--foreground);
    background: transparent;
    border: 1px solid transparent;
    border-radius: 0.25rem;
    cursor: pointer;
    transition: all 0.1s ease;
    min-width: 4rem;
}

.move-button:hover {
    background-color: var(--accent);
}

.move-button.is-current {
    background-color: var(--go-green);
    color: white;
    border-color: var(--go-green-hover);
}

.move-button.has-variation {
    font-style: italic;
}

.stone-indicator {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    flex-shrink: 0;
}

.stone-indicator.black {
    background-color: #1a1a1a;
}

.stone-indicator.white {
    background-color: #f5f5f5;
    border: 1px solid var(--border);
}

.move-notation {
    font-family: monospace;
}

.move-symbols {
    font-weight: 700;
    color: #f59e0b;
}

.annotation-icon {
    color: #3b82f6;
    flex-shrink: 0;
}

.branch-icon {
    color: #a855f7;
    flex-shrink: 0;
}

.move-placeholder {
    min-width: 4rem;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    color: var(--muted-foreground);
}
</style>
