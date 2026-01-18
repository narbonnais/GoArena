<script setup lang="ts">
import { ref } from 'vue';

import type { MoveNode } from '@/types/analysis';
import type { Coordinate } from '@/types/go';

import MoveTable from './MoveTable.vue';
import VisualMoveTree from './VisualMoveTree.vue';

const props = defineProps<{
    nodes: Record<string, MoveNode>;
    rootId: string;
    currentNodeId: string;
    boardSize: number;
    winRateHistory?: { moveNumber: number; blackWinRate: number }[];
}>();

const emit = defineEmits<{
    (e: 'select-node', nodeId: string): void;
    (e: 'hover-move', coordinate: Coordinate | null): void;
}>();

type Tab = 'moves' | 'tree';
const activeTab = ref<Tab>('moves');

function handleSelectNode(nodeId: string) {
    emit('select-node', nodeId);
}

function handleHoverMove(coordinate: Coordinate | null) {
    emit('hover-move', coordinate);
}
</script>

<template>
    <div class="moves-panel-tabs">
        <!-- Tab headers -->
        <div class="tabs-header">
            <button
                class="tab-btn"
                :class="{ active: activeTab === 'moves' }"
                @click="activeTab = 'moves'"
            >
                Moves
            </button>
            <button
                class="tab-btn"
                :class="{ active: activeTab === 'tree' }"
                @click="activeTab = 'tree'"
            >
                Move Tree
            </button>
        </div>

        <!-- Tab content -->
        <div class="tabs-content">
            <div v-show="activeTab === 'moves'" class="tab-pane">
                <MoveTable
                    :nodes="nodes"
                    :root-id="rootId"
                    :current-node-id="currentNodeId"
                    :board-size="boardSize"
                    :win-rate-history="winRateHistory"
                    @select-node="handleSelectNode"
                    @hover-move="handleHoverMove"
                />
            </div>
            <div v-show="activeTab === 'tree'" class="tab-pane">
                <VisualMoveTree
                    :nodes="nodes"
                    :root-id="rootId"
                    :current-node-id="currentNodeId"
                    :board-size="boardSize"
                    @select-node="handleSelectNode"
                    @hover-move="handleHoverMove"
                />
            </div>
        </div>
    </div>
</template>

<style scoped>
.moves-panel-tabs {
    display: flex;
    flex-direction: column;
    background-color: var(--card);
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    height: 100%;
    min-height: 0;
}

.tabs-header {
    display: flex;
    border-bottom: 1px solid var(--border);
    background-color: var(--background);
    flex-shrink: 0;
}

.tab-btn {
    flex: 1;
    padding: 0.5rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted-foreground);
    background: transparent;
    border: none;
    cursor: pointer;
    transition: all 0.15s ease;
    position: relative;
}

.tab-btn:hover {
    color: var(--foreground);
    background-color: var(--accent);
}

.tab-btn.active {
    color: var(--foreground);
}

.tab-btn.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 2px;
    background-color: var(--go-green);
}

.tabs-content {
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

.tab-pane {
    height: 100%;
    overflow: auto;
}

/* Override child component styles for proper fit */
.tab-pane :deep(.move-table) {
    border: none;
    border-radius: 0;
    min-height: auto;
    height: 100%;
}

.tab-pane :deep(.visual-move-tree) {
    border-radius: 0;
    height: 100%;
}

.tab-pane :deep(.tree-container) {
    max-height: none;
    flex: 1;
}

.tab-pane :deep(.tree-header),
.tab-pane :deep(.table-header) {
    display: none;
}
</style>
