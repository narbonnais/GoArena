<script setup lang="ts">
import { computed } from 'vue';

import type { BoardState, Coordinate, Stone } from '@/types/go';
import type { LessonHighlight } from '@/types/learn';

import GoStone from '../GoStone.vue';

const props = defineProps<{
    board: BoardState;
    size: 9 | 13 | 19;
    disabled?: boolean;
    highlights?: LessonHighlight[];
    showHintMarkers?: boolean;
    hintCoordinates?: Coordinate[];
    feedback?: 'correct' | 'incorrect' | null;
}>();

const emit = defineEmits<{
    (e: 'play', coord: Coordinate): void;
}>();

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
        points.push({ x: 2, y: 2 }, { x: 6, y: 2 }, { x: 4, y: 4 }, { x: 2, y: 6 }, { x: 6, y: 6 });
    } else if (props.size === 13) {
        points.push(
            { x: 3, y: 3 },
            { x: 9, y: 3 },
            { x: 6, y: 6 },
            { x: 3, y: 9 },
            { x: 9, y: 9 },
        );
    } else if (props.size === 19) {
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

// Get highlight color with default
function getHighlightColor(highlight: LessonHighlight): string {
    return highlight.color || '#22c55e';
}
</script>

<template>
    <div class="tutorial-board-container" :class="{ 'feedback-correct': feedback === 'correct', 'feedback-incorrect': feedback === 'incorrect' }">
        <svg
            :width="boardWidth"
            :height="boardHeight"
            :viewBox="`0 0 ${boardWidth} ${boardHeight}`"
            class="tutorial-board"
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

            <!-- Coordinate labels -->
            <g class="coordinates">
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

            <!-- Highlights layer (below stones) -->
            <g v-if="highlights" class="highlights">
                <template v-for="(highlight, hIdx) in highlights" :key="`highlight-${hIdx}`">
                    <template v-for="(coord, cIdx) in highlight.coordinates" :key="`h-${hIdx}-${cIdx}`">
                        <!-- Circle highlight -->
                        <circle
                            v-if="highlight.type === 'circle'"
                            :cx="toSvgX(coord.x)"
                            :cy="toSvgY(coord.y)"
                            :r="cellSize * 0.4"
                            :stroke="getHighlightColor(highlight)"
                            stroke-width="3"
                            fill="none"
                            class="highlight-circle"
                        />
                        <!-- Square highlight -->
                        <rect
                            v-else-if="highlight.type === 'square'"
                            :x="toSvgX(coord.x) - cellSize * 0.35"
                            :y="toSvgY(coord.y) - cellSize * 0.35"
                            :width="cellSize * 0.7"
                            :height="cellSize * 0.7"
                            :stroke="getHighlightColor(highlight)"
                            stroke-width="3"
                            fill="none"
                            class="highlight-square"
                        />
                    </template>
                </template>
            </g>

            <!-- Clickable intersections -->
            <g class="intersections">
                <template v-for="y in size" :key="`row-${y}`">
                    <rect
                        v-for="x in size"
                        :key="`int-${x}-${y}`"
                        :x="toSvgX(x - 1) - cellSize / 2"
                        :y="toSvgY(y - 1) - cellSize / 2"
                        :width="cellSize"
                        :height="cellSize"
                        class="intersection"
                        :class="{ disabled: disabled }"
                        @click="handleClick(x - 1, y - 1)"
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
                            :is-last-move="false"
                        />
                    </template>
                </template>
            </g>

            <!-- Hint markers -->
            <g v-if="showHintMarkers && hintCoordinates" class="hint-markers">
                <template v-for="(coord, index) in hintCoordinates" :key="`hint-${index}`">
                    <g v-if="board[coord.y]?.[coord.x] === null">
                        <circle
                            :cx="toSvgX(coord.x)"
                            :cy="toSvgY(coord.y)"
                            :r="cellSize * 0.25"
                            class="hint-marker"
                        />
                    </g>
                </template>
            </g>
        </svg>

        <!-- Feedback overlay -->
        <div v-if="feedback" class="feedback-overlay" :class="feedback">
            <div class="feedback-icon">
                <svg v-if="feedback === 'correct'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </div>
        </div>
    </div>
</template>

<style scoped>
.tutorial-board-container {
    position: relative;
    display: inline-block;
    border-radius: 8px;
    box-shadow:
        0 4px 6px -1px rgba(0, 0, 0, 0.1),
        0 2px 4px -1px rgba(0, 0, 0, 0.06);
    transition: box-shadow 0.3s ease;
}

.tutorial-board-container.feedback-correct {
    box-shadow:
        0 0 0 4px rgba(34, 197, 94, 0.4),
        0 4px 6px -1px rgba(0, 0, 0, 0.1);
}

.tutorial-board-container.feedback-incorrect {
    box-shadow:
        0 0 0 4px rgba(239, 68, 68, 0.4),
        0 4px 6px -1px rgba(0, 0, 0, 0.1);
    animation: shake 0.3s ease-in-out;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
}

.tutorial-board {
    display: block;
}

.board-background {
    fill: #ddb06d;
}

.grid-line {
    stroke: #1a1a1a;
    stroke-width: 1;
}

.star-point {
    fill: #1a1a1a;
}

.coord-label {
    font-size: 12px;
    font-family: system-ui, sans-serif;
    fill: #5c4a32;
    text-anchor: middle;
    dominant-baseline: middle;
    user-select: none;
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

/* Highlights */
.highlight-circle,
.highlight-square {
    pointer-events: none;
    opacity: 0.8;
}

/* Hint markers */
.hint-marker {
    fill: rgba(234, 179, 8, 0.6);
    stroke: #eab308;
    stroke-width: 2;
    pointer-events: none;
    animation: pulse 1.5s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 0.6; }
    50% { opacity: 1; }
}

/* Feedback overlay */
.feedback-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: fadeInScale 0.3s ease-out;
    pointer-events: none;
}

.feedback-overlay.correct {
    background-color: rgba(34, 197, 94, 0.9);
}

.feedback-overlay.incorrect {
    background-color: rgba(239, 68, 68, 0.9);
}

.feedback-icon {
    width: 48px;
    height: 48px;
    color: white;
}

.feedback-icon svg {
    width: 100%;
    height: 100%;
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: translate(-50%, -50%) scale(0.5);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%) scale(1);
    }
}
</style>
