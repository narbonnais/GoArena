<script setup lang="ts">
import { computed } from 'vue';

import type { MoveNode, MoveSymbol } from '@/types/analysis';
import type { Coordinate } from '@/types/go';

const props = defineProps<{
    nodes: Record<string, MoveNode>;
    rootId: string;
    currentNodeId: string;
    boardSize: number;
}>();

const emit = defineEmits<{
    (e: 'select-node', nodeId: string): void;
    (e: 'hover-move', coordinate: Coordinate | null): void;
}>();

// Coordinate to notation (Q11, K7, etc.)
function coordToNotation(coord: Coordinate | null): string {
    if (!coord) return 'Pass';
    const letters = 'ABCDEFGHJKLMNOPQRST';
    return `${letters[coord.x]}${props.boardSize - coord.y}`;
}

// Symbol display
const SYMBOLS: Record<MoveSymbol, string> = {
    good: '!',
    bad: '?',
    interesting: '!?',
    doubtful: '?!',
    brilliant: '!!',
    blunder: '??',
};

// Recursive tree building for inline display
interface TreeElement {
    type: 'move' | 'comment' | 'variation-start' | 'variation-end';
    node?: MoveNode;
    text?: string;
    depth: number;
}

const treeElements = computed<TreeElement[]>(() => {
    const elements: TreeElement[] = [];

    function processNode(nodeId: string, depth: number, isFirst: boolean) {
        const node = props.nodes[nodeId];
        if (!node) return;

        if (node.id === props.rootId) {
            // Process children of root
            if (node.children.length > 0) {
                processNode(node.children[0], 0, true);
            }
            return;
        }

        // Add move
        elements.push({ type: 'move', node, depth });

        // Add comment if exists
        if (node.comment) {
            elements.push({ type: 'comment', text: node.comment, depth });
        }

        // Process children
        if (node.children.length > 0) {
            // Main continuation
            processNode(node.children[0], depth, false);

            // Variations (in parentheses)
            for (let i = 1; i < node.children.length; i++) {
                elements.push({ type: 'variation-start', depth: depth + 1 });
                processNode(node.children[i], depth + 1, true);
                elements.push({ type: 'variation-end', depth: depth + 1 });
            }
        }
    }

    processNode(props.rootId, 0, true);
    return elements;
});

// Determine if a move needs a move number displayed
function shouldShowMoveNumber(el: TreeElement, index: number): boolean {
    if (!el.node || el.node.stone !== 'black') return false;

    // Always show for first move
    if (index === 0) return true;

    // Show after a comment or at start of variation
    const prev = treeElements.value[index - 1];
    if (!prev) return true;

    return prev.type === 'comment' || prev.type === 'variation-start';
}

// For white moves after variation start, show the move number with ellipsis
function shouldShowWhiteMoveNumber(el: TreeElement, index: number): boolean {
    if (!el.node || el.node.stone !== 'white') return false;

    const prev = treeElements.value[index - 1];
    if (!prev) return false;

    return prev.type === 'variation-start' || prev.type === 'comment';
}

function getMoveNumber(node: MoveNode): number {
    return Math.ceil(node.moveNumber / 2);
}
</script>

<template>
    <div class="move-tree">
        <div class="tree-header">
            <span class="header-title">Moves</span>
        </div>
        <div class="tree-content">
            <template v-for="(el, i) in treeElements" :key="i">
                <span v-if="el.type === 'variation-start'" class="variation-paren">(</span>
                <span v-else-if="el.type === 'variation-end'" class="variation-paren">) </span>
                <span v-else-if="el.type === 'comment'" class="move-comment">{{ el.text }} </span>
                <button
                    v-else-if="el.type === 'move' && el.node"
                    class="move-btn"
                    :class="{
                        current: el.node.id === currentNodeId,
                        'in-variation': el.depth > 0,
                        black: el.node.stone === 'black',
                        white: el.node.stone === 'white'
                    }"
                    @click="emit('select-node', el.node.id)"
                    @mouseenter="emit('hover-move', el.node.coordinate)"
                    @mouseleave="emit('hover-move', null)"
                >
                    <span v-if="shouldShowMoveNumber(el, i)" class="move-num">
                        {{ getMoveNumber(el.node) }}.
                    </span>
                    <span v-else-if="shouldShowWhiteMoveNumber(el, i)" class="move-num">
                        {{ getMoveNumber(el.node) }}...
                    </span>
                    <span class="stone-dot" :class="el.node.stone"></span>
                    <span class="coord">{{ coordToNotation(el.node.coordinate) }}</span>
                    <span v-if="el.node.symbols.length" class="symbols">
                        {{ el.node.symbols.map(s => SYMBOLS[s]).join('') }}
                    </span>
                </button>
            </template>
            <span v-if="treeElements.length === 0" class="empty">
                Play moves on the board
            </span>
        </div>
    </div>
</template>

<style scoped>
.move-tree {
    display: flex;
    flex-direction: column;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    overflow: hidden;
}

.tree-header {
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

.tree-content {
    padding: 1rem;
    line-height: 1.8;
    font-size: 0.9rem;
    max-height: 400px;
    overflow-y: auto;
}

.move-btn {
    display: inline-flex;
    align-items: center;
    gap: 2px;
    padding: 2px 4px;
    border-radius: 3px;
    cursor: pointer;
    border: none;
    background: transparent;
    font-size: inherit;
    font-family: inherit;
    color: var(--foreground);
    vertical-align: middle;
    transition: background-color 0.1s ease;
}

.move-btn:hover {
    background-color: var(--accent);
}

.move-btn.current {
    background-color: var(--go-green);
    color: white;
}

.move-btn.in-variation {
    opacity: 0.85;
}

.move-num {
    font-weight: 600;
    margin-right: 2px;
}

.stone-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}

.stone-dot.black {
    background: #1a1a1a;
}

.stone-dot.white {
    background: #f5f5f5;
    border: 1px solid #ccc;
}

.coord {
    font-family: monospace;
}

.move-comment {
    font-style: italic;
    color: var(--muted-foreground);
    font-size: 0.85em;
}

.variation-paren {
    color: var(--muted-foreground);
    font-weight: 500;
}

.symbols {
    color: #f59e0b;
    font-weight: bold;
}

.empty {
    color: var(--muted-foreground);
    font-size: 0.875rem;
}
</style>
