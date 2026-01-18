<script setup lang="ts">
import { computed, ref } from 'vue';

import type { BoardState, Coordinate, Move, Stone } from '@/types/go';

import GoStone from './GoStone.vue';

interface SuggestedMoveWithWinRate extends Coordinate {
    winRate?: number;
}

export type BoardTheme = 'classic' | 'kaya' | 'slate';

const props = withDefaults(defineProps<{
    board: BoardState;
    size: number;
    currentPlayer: Stone;
    lastMove: Move | null;
    disabled?: boolean;
    suggestedMoves?: SuggestedMoveWithWinRate[];
    showSuggestions?: boolean;
    hoveredCoordinate?: Coordinate | null;
    showCoordinates?: boolean;
    boardTheme?: BoardTheme;
    deadStones?: Coordinate[];
    interactionMode?: 'play' | 'mark-dead';
}>(), {
    showCoordinates: true,
    boardTheme: 'classic',
    deadStones: () => [],
    interactionMode: 'play',
});

const emit = defineEmits<{
    (e: 'play', coord: Coordinate): void;
}>();

// Ghost stone hover state
const ghostStone = ref<Coordinate | null>(null);

// Board dimensions
const cellSize = 40;
const padding = 30;
const boardWidth = computed(() => (props.size - 1) * cellSize + padding * 2);
const boardHeight = computed(() => (props.size - 1) * cellSize + padding * 2);

// Coordinate labels
const columnLabels = computed(() => {
    const letters = 'ABCDEFGHJKLMNOPQRST'; // Skip 'I' in Go
    return Array.from({ length: props.size }, (_, i) => letters[i]);
});

const rowLabels = computed(() => {
    return Array.from({ length: props.size }, (_, i) => props.size - i);
});

// Star points (hoshi)
const starPoints = computed(() => {
    const points: Coordinate[] = [];

    if (props.size === 9) {
        // 9x9: center and 4 corners
        points.push({ x: 2, y: 2 }, { x: 6, y: 2 }, { x: 4, y: 4 }, { x: 2, y: 6 }, { x: 6, y: 6 });
    } else if (props.size === 13) {
        // 13x13: corners, sides, and center
        points.push(
            { x: 3, y: 3 },
            { x: 9, y: 3 },
            { x: 6, y: 6 },
            { x: 3, y: 9 },
            { x: 9, y: 9 },
        );
    } else if (props.size === 19) {
        // 19x19: standard 9 star points
        for (const x of [3, 9, 15]) {
            for (const y of [3, 9, 15]) {
                points.push({ x, y });
            }
        }
    }

    return points;
});

// Convert board coordinates to SVG coordinates
function toSvgX(x: number): number {
    return padding + x * cellSize;
}

function toSvgY(y: number): number {
    return padding + y * cellSize;
}

// Handle click on intersection
function handleClick(x: number, y: number) {
    if (props.disabled) return;
    emit('play', { x, y });
}

// Handle touch on intersection (for mobile)
function handleTouch(event: TouchEvent, x: number, y: number) {
    if (props.disabled) return;
    event.preventDefault(); // Prevent double-firing with click
    emit('play', { x, y });
}

// Check if a coordinate is the last move
function isLastMove(x: number, y: number): boolean {
    return props.lastMove?.coordinate?.x === x && props.lastMove?.coordinate?.y === y;
}

// Check if a coordinate is a suggested move and return its rank (1-based)
function getSuggestionRank(x: number, y: number): number {
    if (!props.showSuggestions || !props.suggestedMoves) return 0;
    const index = props.suggestedMoves.findIndex((c) => c.x === x && c.y === y);
    return index >= 0 ? index + 1 : 0;
}

// Generate coordinate label for accessibility (e.g., "D4")
function getCoordLabel(x: number, y: number): string {
    const letters = 'ABCDEFGHJKLMNOPQRST'; // Skip 'I' in Go
    return `${letters[x]}${props.size - y}`;
}

// Generate ARIA label for an intersection
function getIntersectionAriaLabel(x: number, y: number): string {
    const coord = getCoordLabel(x, y);
    const stone = props.board[y]?.[x];
    if (stone === 'black') return `${coord}, black stone`;
    if (stone === 'white') return `${coord}, white stone`;
    return `${coord}, empty`;
}

// Ghost stone hover handlers
function handleMouseEnter(x: number, y: number) {
    if (props.disabled || props.interactionMode !== 'play') return;
    // Only show ghost if position is empty
    if (props.board[y]?.[x] === null) {
        ghostStone.value = { x, y };
    }
}

function handleMouseLeave() {
    ghostStone.value = null;
}

// Check if position should show ghost stone
function isGhostPosition(x: number, y: number): boolean {
    return ghostStone.value?.x === x && ghostStone.value?.y === y;
}

// Touch device: two-tap behavior
const pendingTouchMove = ref<Coordinate | null>(null);

function handleTouchStart(event: TouchEvent, x: number, y: number) {
    if (props.disabled) return;
    if (props.interactionMode === 'mark-dead') {
        event.preventDefault();
        emit('play', { x, y });
        pendingTouchMove.value = null;
        ghostStone.value = null;
        return;
    }

    // Check if position is empty
    if (props.board[y]?.[x] !== null) return;

    // If this is the same position as pending, confirm the move
    if (pendingTouchMove.value?.x === x && pendingTouchMove.value?.y === y) {
        event.preventDefault();
        emit('play', { x, y });
        pendingTouchMove.value = null;
        ghostStone.value = null;
    } else {
        // First tap - show preview
        event.preventDefault();
        pendingTouchMove.value = { x, y };
        ghostStone.value = { x, y };
    }
}

// Clear pending touch on touch end outside board
function clearPendingTouch() {
    // Delay to allow second tap
    setTimeout(() => {
        if (pendingTouchMove.value) {
            pendingTouchMove.value = null;
            ghostStone.value = null;
        }
    }, 3000); // Clear after 3 seconds if no second tap
}

// Board theme colors
const themeColors = computed(() => {
    switch (props.boardTheme) {
        case 'kaya':
            return {
                wood: '#e8c47c',
                woodDark: '#c9a055',
            };
        case 'slate':
            return {
                wood: '#5a6a72',
                woodDark: '#4a5a62',
            };
        default: // classic
            return {
                wood: '#d7a868',
                woodDark: '#b98c52',
            };
    }
});
</script>

<template>
    <div
        class="go-board-container"
        role="application"
        aria-label="Go game board"
        :style="{
            '--theme-wood': themeColors.wood,
            '--theme-wood-dark': themeColors.woodDark,
        }"
        @touchend="clearPendingTouch"
    >
        <template v-if="board && Array.isArray(board) && board.length > 0">
            <svg
                :viewBox="`0 0 ${boardWidth} ${boardHeight}`"
                class="go-board"
                role="grid"
                :aria-label="`${size} by ${size} Go board`"
                preserveAspectRatio="xMidYMid meet"
            >
            <!-- Board background -->
            <rect x="0" y="0" :width="boardWidth" :height="boardHeight" class="board-background" />

            <!-- Grid lines -->
            <g class="grid-lines">
                <!-- Vertical lines -->
                <line
                    v-for="i in size"
                    :key="`v-${i}`"
                    :x1="toSvgX(i - 1)"
                    :y1="toSvgY(0)"
                    :x2="toSvgX(i - 1)"
                    :y2="toSvgY(size - 1)"
                    class="grid-line"
                />
                <!-- Horizontal lines -->
                <line
                    v-for="i in size"
                    :key="`h-${i}`"
                    :x1="toSvgX(0)"
                    :y1="toSvgY(i - 1)"
                    :x2="toSvgX(size - 1)"
                    :y2="toSvgY(i - 1)"
                    class="grid-line"
                />
            </g>

            <!-- Star points (hoshi) -->
            <g class="star-points">
                <circle
                    v-for="(point, idx) in starPoints"
                    :key="`star-${idx}`"
                    :cx="toSvgX(point.x)"
                    :cy="toSvgY(point.y)"
                    r="4"
                    class="star-point"
                />
            </g>

            <!-- Coordinate labels (conditional) -->
            <g v-if="showCoordinates" class="coordinates">
                <!-- Column labels (letters) - top -->
                <text
                    v-for="(label, i) in columnLabels"
                    :key="`col-top-${i}`"
                    :x="toSvgX(i)"
                    :y="padding - 12"
                    class="coord-label"
                >
                    {{ label }}
                </text>
                <!-- Column labels (letters) - bottom -->
                <text
                    v-for="(label, i) in columnLabels"
                    :key="`col-bot-${i}`"
                    :x="toSvgX(i)"
                    :y="boardHeight - padding + 20"
                    class="coord-label"
                >
                    {{ label }}
                </text>
                <!-- Row labels (numbers) - left -->
                <text
                    v-for="(label, i) in rowLabels"
                    :key="`row-left-${i}`"
                    :x="padding - 18"
                    :y="toSvgY(i) + 5"
                    class="coord-label"
                >
                    {{ label }}
                </text>
                <!-- Row labels (numbers) - right -->
                <text
                    v-for="(label, i) in rowLabels"
                    :key="`row-right-${i}`"
                    :x="boardWidth - padding + 12"
                    :y="toSvgY(i) + 5"
                    class="coord-label"
                >
                    {{ label }}
                </text>
            </g>

            <!-- Clickable intersections -->
            <g class="intersections" role="rowgroup">
                <template v-for="y in size" :key="`row-${y}`">
                    <rect
                        v-for="x in size"
                        :key="`int-${x}-${y}`"
                        :x="toSvgX(x - 1) - cellSize / 2"
                        :y="toSvgY(y - 1) - cellSize / 2"
                        :width="cellSize"
                        :height="cellSize"
                        class="intersection"
                        :class="{
                            disabled: disabled,
                            pending: pendingTouchMove?.x === x - 1 && pendingTouchMove?.y === y - 1
                        }"
                        role="gridcell"
                        tabindex="0"
                        :aria-label="getIntersectionAriaLabel(x - 1, y - 1)"
                        :aria-disabled="disabled"
                        @click="handleClick(x - 1, y - 1)"
                        @touchstart="handleTouchStart($event, x - 1, y - 1)"
                        @keydown.enter="handleClick(x - 1, y - 1)"
                        @keydown.space.prevent="handleClick(x - 1, y - 1)"
                        @mouseenter="handleMouseEnter(x - 1, y - 1)"
                        @mouseleave="handleMouseLeave"
                    />
                </template>
            </g>

            <!-- Stones -->
            <g class="stones">
                <template v-for="(row, y) in board" :key="`row-${y}`">
                    <template v-for="(stone, x) in row" :key="`stone-${x}-${y}`">
                        <GoStone
                            v-if="stone !== null"
                            :color="stone"
                            :x="toSvgX(x)"
                            :y="toSvgY(y)"
                            :cell-size="cellSize"
                            :is-last-move="isLastMove(x, y)"
                        />
                    </template>
                </template>
            </g>

            <!-- Dead stone markers -->
            <g v-if="deadStones && deadStones.length > 0" class="dead-stones">
                <g v-for="(coord, index) in deadStones" :key="`dead-${index}`">
                    <line
                        :x1="toSvgX(coord.x) - cellSize * 0.35"
                        :y1="toSvgY(coord.y) - cellSize * 0.35"
                        :x2="toSvgX(coord.x) + cellSize * 0.35"
                        :y2="toSvgY(coord.y) + cellSize * 0.35"
                        class="dead-marker"
                    />
                    <line
                        :x1="toSvgX(coord.x) - cellSize * 0.35"
                        :y1="toSvgY(coord.y) + cellSize * 0.35"
                        :x2="toSvgX(coord.x) + cellSize * 0.35"
                        :y2="toSvgY(coord.y) - cellSize * 0.35"
                        class="dead-marker"
                    />
                </g>
            </g>

            <!-- Ghost Stone Preview -->
            <g v-if="ghostStone && !disabled && interactionMode === 'play'" class="ghost-stone">
                <circle
                    :cx="toSvgX(ghostStone.x)"
                    :cy="toSvgY(ghostStone.y)"
                    :r="cellSize * 0.45"
                    :class="['ghost', currentPlayer]"
                />
            </g>

            <!-- Suggested Moves Overlay -->
            <g v-if="showSuggestions && suggestedMoves && suggestedMoves.length > 0" class="suggestions">
                <template v-for="(coord, index) in suggestedMoves" :key="`suggestion-${index}`">
                    <g v-if="board[coord.y]?.[coord.x] === null" class="suggestion-group">
                        <!-- Outer glow for best move -->
                        <circle
                            v-if="index === 0"
                            :cx="toSvgX(coord.x)"
                            :cy="toSvgY(coord.y)"
                            :r="cellSize * 0.48"
                            class="suggestion-glow"
                        />
                        <!-- Suggestion circle - larger -->
                        <circle
                            :cx="toSvgX(coord.x)"
                            :cy="toSvgY(coord.y)"
                            :r="cellSize * 0.42"
                            :class="['suggestion-marker', index === 0 ? 'best' : 'alternative']"
                        />
                        <!-- Rank number -->
                        <text
                            :x="toSvgX(coord.x)"
                            :y="toSvgY(coord.y) + 1"
                            class="suggestion-rank"
                            :class="{ best: index === 0 }"
                        >
                            {{ index + 1 }}
                        </text>
                    </g>
                </template>
            </g>

            <!-- Hovered Move Indicator (from move table) -->
            <g v-if="hoveredCoordinate" class="hovered-move">
                <circle
                    :cx="toSvgX(hoveredCoordinate.x)"
                    :cy="toSvgY(hoveredCoordinate.y)"
                    :r="cellSize * 0.4"
                    class="hover-indicator"
                />
            </g>
            </svg>
        </template>
        <div v-else class="board-loading">
            <span>Loading board...</span>
        </div>
    </div>
</template>

<style scoped>
/* Board container - responsive sizing */
.go-board-container {
    /* Theme colors (overridden by inline style) */
    --board-wood: var(--theme-wood, #d7a868);
    --board-wood-dark: var(--theme-wood-dark, #b98c52);
    --grid-color: #3d2f1a;
    --coord-color: #5c4a32;

    /* Responsive sizing using clamp */
    width: clamp(280px, min(92vw, 70vh), 720px);
    aspect-ratio: 1 / 1;
    display: block;
    border-radius: 4px;
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Slightly darker for dark mode */
:root.dark .go-board-container,
.dark .go-board-container {
    --grid-color: #2a1f0f;
    --coord-color: #4a3a28;
}

.board-loading {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    background-color: var(--board-wood);
    border-radius: 4px;
    color: var(--coord-color);
    font-size: 14px;
}

.go-board {
    display: block;
    width: 100%;
    height: 100%;
}

.board-background {
    fill: var(--board-wood);
}

.grid-line {
    stroke: var(--grid-color);
    stroke-width: 1;
}

.star-point {
    fill: var(--grid-color);
}

.coord-label {
    font-size: 11px;
    font-family: system-ui, -apple-system, sans-serif;
    font-weight: 600;
    fill: var(--coord-color);
    text-anchor: middle;
    dominant-baseline: middle;
    user-select: none;
    letter-spacing: 0.02em;
}

.intersection {
    fill: transparent;
    cursor: pointer;
}

.intersection:hover {
    fill: rgba(0, 0, 0, 0.1);
}

.intersection.disabled {
    cursor: not-allowed;
}

.intersection.disabled:hover {
    fill: transparent;
}

/* Dead stone markers */
.dead-marker {
    stroke: rgba(220, 38, 38, 0.85);
    stroke-width: 3;
    stroke-linecap: round;
    pointer-events: none;
}

/* Ghost stone preview */
.ghost-stone .ghost {
    pointer-events: none;
    opacity: 0.4;
    transition: opacity 0.1s ease;
}

.ghost-stone .ghost.black {
    fill: #1a1a1a;
}

.ghost-stone .ghost.white {
    fill: #f0f0f0;
    stroke: #ccc;
    stroke-width: 1;
}

/* Suggested moves overlay - enhanced */
.suggestion-group {
    pointer-events: none;
}

.suggestion-glow {
    fill: none;
    stroke: rgba(34, 197, 94, 0.4);
    stroke-width: 4;
    animation: pulse-glow 2s ease-in-out infinite;
}

@keyframes pulse-glow {
    0%, 100% { opacity: 0.4; }
    50% { opacity: 0.8; }
}

.suggestion-marker {
    fill: rgba(34, 197, 94, 0.25);
    stroke: #22c55e;
    stroke-width: 2.5;
    pointer-events: none;
}

.suggestion-marker.best {
    fill: rgba(34, 197, 94, 0.4);
    stroke: #16a34a;
    stroke-width: 3;
}

.suggestion-marker.alternative {
    fill: rgba(96, 165, 250, 0.25);
    stroke: #60a5fa;
    stroke-width: 2;
}

.suggestion-rank {
    font-size: 16px;
    font-weight: 800;
    fill: #15803d;
    text-anchor: middle;
    dominant-baseline: central;
    pointer-events: none;
    user-select: none;
}

.suggestion-rank.best {
    fill: #166534;
    font-size: 18px;
}

/* Hovered move indicator (from move table hover) */
.hover-indicator {
    fill: rgba(251, 191, 36, 0.35);
    stroke: #f59e0b;
    stroke-width: 3;
    pointer-events: none;
}
</style>
