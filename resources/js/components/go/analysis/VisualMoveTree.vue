<script setup lang="ts">
import { MessageSquare } from 'lucide-vue-next';
import { computed, ref, watch, nextTick, onMounted } from 'vue';

import { useTreeLayout, type LayoutNode } from '@/composables/go/useTreeLayout';
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

// Layout dimensions
const NODE_WIDTH = 58;
const NODE_HEIGHT = 24;
const H_GAP = 4;
const V_GAP = 20;

// Refs for scrolling
const containerRef = ref<HTMLElement | null>(null);
const contentRef = ref<HTMLElement | null>(null);

// Symbol display
const SYMBOLS: Record<MoveSymbol, string> = {
    good: '!',
    bad: '?',
    blunder: '??',
};

// Compute tree layout
const nodesRef = computed(() => props.nodes);
const rootIdRef = computed(() => props.rootId);
const { layoutNodes, maxX, maxY } = useTreeLayout(nodesRef, rootIdRef);

// Coordinate to notation (Q11, K7, etc.)
function coordToNotation(coord: Coordinate | null): string {
    if (!coord) return 'Pass';
    const letters = 'ABCDEFGHJKLMNOPQRST';
    return `${letters[coord.x]}${props.boardSize - coord.y}`;
}

// Get pixel position from grid position
function getNodeX(x: number): number {
    return x * (NODE_WIDTH + H_GAP);
}

function getNodeY(y: number): number {
    return y * (NODE_HEIGHT + V_GAP);
}

// Computed SVG size
const svgWidth = computed(() => {
    return (maxX.value + 1) * (NODE_WIDTH + H_GAP) + 20;
});

const svgHeight = computed(() => {
    return (maxY.value + 1) * (NODE_HEIGHT + V_GAP) + 20;
});

// Generate connection paths between nodes
const connectionPaths = computed(() => {
    const paths: Array<{
        d: string;
        isMainLine: boolean;
        key: string;
    }> = [];

    for (const layoutNode of layoutNodes.value) {
        if (!layoutNode.parentLayoutNode) continue;

        const parent = layoutNode.parentLayoutNode;
        const startX = getNodeX(parent.x) + NODE_WIDTH;
        const startY = getNodeY(parent.y) + NODE_HEIGHT / 2;
        const endX = getNodeX(layoutNode.x);
        const endY = getNodeY(layoutNode.y) + NODE_HEIGHT / 2;

        // Create bezier curve for smooth connection
        const midX = (startX + endX) / 2;

        let d: string;
        if (startY === endY) {
            // Straight horizontal line for main continuation
            d = `M ${startX} ${startY} L ${endX} ${endY}`;
        } else {
            // Bezier curve for variations branching down
            const controlOffset = Math.min(30, Math.abs(endY - startY) / 2);
            d = `M ${startX} ${startY} C ${startX + controlOffset} ${startY}, ${endX - controlOffset} ${endY}, ${endX} ${endY}`;
        }

        paths.push({
            d,
            isMainLine: layoutNode.isMainLine,
            key: `${parent.id}-${layoutNode.id}`,
        });
    }

    return paths;
});

// Check if node is on current path (from root to current node)
const currentPath = computed(() => {
    const path = new Set<string>();
    let nodeId: string | null = props.currentNodeId;

    while (nodeId) {
        path.add(nodeId);
        const moveNode: MoveNode | undefined = props.nodes[nodeId];
        nodeId = moveNode?.parent ?? null;
    }

    return path;
});

function isOnCurrentPath(nodeId: string): boolean {
    return currentPath.value.has(nodeId);
}

// Node click handler
function handleNodeClick(nodeId: string) {
    emit('select-node', nodeId);
}

// Hover handlers
function handleMouseEnter(node: MoveNode) {
    emit('hover-move', node.coordinate);
}

function handleMouseLeave() {
    emit('hover-move', null);
}

// Auto-scroll to current node
watch(() => props.currentNodeId, async () => {
    await nextTick();
    const currentEl = contentRef.value?.querySelector('.node.current') as HTMLElement;
    if (currentEl && containerRef.value) {
        const containerRect = containerRef.value.getBoundingClientRect();
        const nodeRect = currentEl.getBoundingClientRect();

        // Calculate relative position
        const nodeLeft = nodeRect.left - containerRect.left + containerRef.value.scrollLeft;
        const nodeRight = nodeLeft + nodeRect.width;
        const nodeTop = nodeRect.top - containerRect.top + containerRef.value.scrollTop;

        // Scroll horizontally if needed
        if (nodeRight > containerRef.value.scrollLeft + containerRect.width - 20) {
            containerRef.value.scrollLeft = nodeRight - containerRect.width + 40;
        } else if (nodeLeft < containerRef.value.scrollLeft + 20) {
            containerRef.value.scrollLeft = Math.max(0, nodeLeft - 40);
        }

        // Scroll vertically if needed
        if (nodeTop > containerRef.value.scrollTop + containerRect.height - NODE_HEIGHT - 20) {
            containerRef.value.scrollTop = nodeTop - containerRect.height + NODE_HEIGHT + 40;
        } else if (nodeTop < containerRef.value.scrollTop + 20) {
            containerRef.value.scrollTop = Math.max(0, nodeTop - 40);
        }
    }
});

// Initial scroll to current node on mount
onMounted(async () => {
    await nextTick();
    const currentEl = contentRef.value?.querySelector('.node.current') as HTMLElement;
    if (currentEl && containerRef.value) {
        currentEl.scrollIntoView({ block: 'nearest', inline: 'center' });
    }
});
</script>

<template>
    <div class="visual-move-tree">
        <div class="tree-header">
            <span class="header-title">Move Tree</span>
            <span class="node-count">{{ layoutNodes.length }} nodes</span>
        </div>
        <div ref="containerRef" class="tree-container">
            <div
                ref="contentRef"
                class="tree-content"
                :style="{
                    width: `${svgWidth}px`,
                    height: `${svgHeight}px`,
                }"
            >
                <!-- SVG layer for connection lines -->
                <svg
                    class="connections-layer"
                    :width="svgWidth"
                    :height="svgHeight"
                >
                    <path
                        v-for="path in connectionPaths"
                        :key="path.key"
                        :d="path.d"
                        class="connection"
                        :class="{ 'main-line': path.isMainLine }"
                    />
                </svg>

                <!-- HTML layer for clickable nodes -->
                <div
                    v-for="layoutNode in layoutNodes"
                    :key="layoutNode.id"
                    class="node"
                    :class="{
                        current: layoutNode.id === currentNodeId,
                        'on-path': isOnCurrentPath(layoutNode.id),
                        variation: !layoutNode.isMainLine,
                    }"
                    :style="{
                        left: `${getNodeX(layoutNode.x)}px`,
                        top: `${getNodeY(layoutNode.y)}px`,
                        width: `${NODE_WIDTH}px`,
                        height: `${NODE_HEIGHT}px`,
                    }"
                    :title="layoutNode.node.comment || undefined"
                    @click="handleNodeClick(layoutNode.id)"
                    @mouseenter="handleMouseEnter(layoutNode.node)"
                    @mouseleave="handleMouseLeave"
                >
                    <!-- Stone color indicator -->
                    <span
                        class="stone-indicator"
                        :class="layoutNode.node.stone"
                    ></span>

                    <!-- Move notation -->
                    <span class="move-text">
                        {{ coordToNotation(layoutNode.node.coordinate) }}
                    </span>

                    <!-- Symbols (!, ?, ??) -->
                    <span v-if="layoutNode.node.symbols.length" class="symbols">
                        {{ layoutNode.node.symbols.map(s => SYMBOLS[s]).join('') }}
                    </span>

                    <!-- Comment indicator -->
                    <MessageSquare
                        v-if="layoutNode.node.comment"
                        :size="10"
                        class="comment-icon"
                    />
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="layoutNodes.length === 0" class="empty-state">
                Play moves on the board to see the tree
            </div>
        </div>
    </div>
</template>

<style scoped>
.visual-move-tree {
    display: flex;
    flex-direction: column;
    background-color: var(--card);
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.tree-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.5rem 0.75rem;
    border-bottom: 1px solid var(--border);
    background-color: var(--background);
    flex-shrink: 0;
}

.header-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.node-count {
    font-size: 0.7rem;
    color: var(--muted-foreground);
}

.tree-container {
    position: relative;
    overflow: auto;
    max-height: 300px;
    min-height: 100px;
}

.tree-content {
    position: relative;
    min-width: 100%;
    padding: 10px;
}

/* SVG connections layer */
.connections-layer {
    position: absolute;
    top: 0;
    left: 0;
    pointer-events: none;
}

.connection {
    fill: none;
    stroke: var(--muted-foreground);
    stroke-width: 1.5;
    opacity: 0.5;
}

.connection.main-line {
    stroke: var(--foreground);
    stroke-width: 2;
    opacity: 0.7;
}

/* Node styling */
.node {
    position: absolute;
    display: flex;
    align-items: center;
    gap: 3px;
    padding: 0 5px;
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 4px;
    cursor: pointer;
    transition: all 0.1s ease;
    font-size: 0.7rem;
}

.node:hover {
    background-color: var(--accent);
    border-color: var(--accent);
}

.node.current {
    background-color: var(--go-green);
    border-color: var(--go-green);
    color: white;
}

.node.current .move-text {
    color: white;
}

.node.current .comment-icon {
    color: rgba(255, 255, 255, 0.8);
}

.node.on-path:not(.current) {
    border-color: var(--go-green);
    border-width: 1.5px;
}

.node.variation {
    opacity: 0.85;
}

.node.variation:hover {
    opacity: 1;
}

/* Stone indicator */
.stone-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    flex-shrink: 0;
}

.stone-indicator.black {
    background-color: #1a1a1a;
}

.stone-indicator.white {
    background-color: #f5f5f5;
    border: 1px solid #ccc;
}

.node.current .stone-indicator.white {
    border-color: rgba(255, 255, 255, 0.5);
}

/* Move text */
.move-text {
    font-family: monospace;
    font-weight: 600;
    color: var(--foreground);
    white-space: nowrap;
    font-size: 0.7rem;
}

/* Symbols */
.symbols {
    color: #f59e0b;
    font-weight: 700;
    font-size: 0.65rem;
    flex-shrink: 0;
}

.node.current .symbols {
    color: #fde68a;
}

/* Comment icon */
.comment-icon {
    color: var(--muted-foreground);
    flex-shrink: 0;
}

/* Empty state */
.empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: 100px;
    color: var(--muted-foreground);
    font-size: 0.8rem;
}
</style>
