<script setup lang="ts">
import { computed, ref } from 'vue';

interface WinRatePoint {
    moveNumber: number;
    blackWinRate: number;
    stone: 'black' | 'white';
}

const props = defineProps<{
    history: WinRatePoint[];
    currentMoveNumber: number;
}>();

const emit = defineEmits<{
    (e: 'select-move', moveNumber: number): void;
}>();

const containerRef = ref<HTMLElement | null>(null);

// Hover state for tooltip
const hoveredPoint = ref<{ moveNumber: number; blackWinRate: number; x: number; y: number } | null>(null);

// Graph dimensions - increased height for better visibility
const height = 140;
const padding = { top: 12, right: 12, bottom: 24, left: 12 };

const graphWidth = computed(() => 100 - padding.left - padding.right);
const graphHeight = computed(() => height - padding.top - padding.bottom);

// Get the maximum move number in history (for scaling)
const maxMoveNumber = computed(() => {
    if (props.history.length === 0) return 10;
    return Math.max(props.history[props.history.length - 1]?.moveNumber ?? 10, 10);
});

// Scale functions
const xScale = computed(() => {
    return (moveNumber: number) => {
        return padding.left + (moveNumber / maxMoveNumber.value) * graphWidth.value;
    };
});

const yScale = computed(() => {
    return (winRate: number) => {
        // Invert Y: 100% at top, 0% at bottom
        return padding.top + ((100 - winRate) / 100) * graphHeight.value;
    };
});

// Generate SVG path for the win rate line
const linePath = computed(() => {
    if (props.history.length === 0) return '';

    const points = props.history.map((point) => {
        const x = xScale.value(point.moveNumber);
        const y = yScale.value(point.blackWinRate);
        return `${x},${y}`;
    });

    return `M ${points.join(' L ')}`;
});

// Area path for black advantage (above 50% line)
const blackAreaPath = computed(() => {
    if (props.history.length === 0) return '';

    const midY = yScale.value(50);
    const points: string[] = [];

    // Start at mid line
    const firstX = xScale.value(props.history[0].moveNumber);
    points.push(`M ${firstX},${midY}`);

    // Draw line through points, clamping to mid line
    for (const point of props.history) {
        const x = xScale.value(point.moveNumber);
        const y = Math.min(yScale.value(point.blackWinRate), midY); // Clamp to not go below 50%
        points.push(`L ${x},${y}`);
    }

    // Close back to mid line
    const lastX = xScale.value(props.history[props.history.length - 1].moveNumber);
    points.push(`L ${lastX},${midY} Z`);

    return points.join(' ');
});

// Area path for white advantage (below 50% line)
const whiteAreaPath = computed(() => {
    if (props.history.length === 0) return '';

    const midY = yScale.value(50);
    const points: string[] = [];

    // Start at mid line
    const firstX = xScale.value(props.history[0].moveNumber);
    points.push(`M ${firstX},${midY}`);

    // Draw line through points, clamping to mid line
    for (const point of props.history) {
        const x = xScale.value(point.moveNumber);
        const y = Math.max(yScale.value(point.blackWinRate), midY); // Clamp to not go above 50%
        points.push(`L ${x},${y}`);
    }

    // Close back to mid line
    const lastX = xScale.value(props.history[props.history.length - 1].moveNumber);
    points.push(`L ${lastX},${midY} Z`);

    return points.join(' ');
});

// Current position marker
const currentPosition = computed(() => {
    if (props.currentMoveNumber <= 0) {
        return null;
    }
    const point = props.history.find(h => h.moveNumber === props.currentMoveNumber);
    if (!point) return null;

    return {
        x: xScale.value(point.moveNumber),
        y: yScale.value(point.blackWinRate),
        winRate: point.blackWinRate,
    };
});

// 50% line position
const midLineY = computed(() => yScale.value(50));

// Click handler for move selection
function handleClick(event: MouseEvent) {
    if (props.history.length === 0) return;

    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const percentX = (clickX / rect.width) * 100;

    // Convert to move number using the same scale as the graph
    const relativeX = (percentX - padding.left) / graphWidth.value;
    const clickedMoveNumber = Math.round(relativeX * maxMoveNumber.value);

    // Find the closest move in history to the clicked position
    let closestMove = props.history[0];
    let closestDistance = Math.abs(closestMove.moveNumber - clickedMoveNumber);

    for (const point of props.history) {
        const distance = Math.abs(point.moveNumber - clickedMoveNumber);
        if (distance < closestDistance) {
            closestDistance = distance;
            closestMove = point;
        }
    }

    if (closestMove) {
        emit('select-move', closestMove.moveNumber);
    }
}

// Determine overall trend
const trend = computed(() => {
    if (props.history.length < 2) return 'even';
    const last = props.history[props.history.length - 1];
    if (last.blackWinRate > 55) return 'black';
    if (last.blackWinRate < 45) return 'white';
    return 'even';
});

// Handle mouse move for tooltip
function handleMouseMove(event: MouseEvent) {
    if (props.history.length === 0) {
        hoveredPoint.value = null;
        return;
    }

    const target = event.currentTarget as HTMLElement;
    const rect = target.getBoundingClientRect();
    const mouseX = event.clientX - rect.left;
    const percentX = (mouseX / rect.width) * 100;

    const relativeX = (percentX - padding.left) / graphWidth.value;
    const hoveredMoveNumber = Math.round(relativeX * maxMoveNumber.value);

    // Find closest point
    let closestPoint = props.history[0];
    let closestDistance = Math.abs(closestPoint.moveNumber - hoveredMoveNumber);

    for (const point of props.history) {
        const distance = Math.abs(point.moveNumber - hoveredMoveNumber);
        if (distance < closestDistance) {
            closestDistance = distance;
            closestPoint = point;
        }
    }

    if (closestPoint && closestDistance <= 3) {
        hoveredPoint.value = {
            moveNumber: closestPoint.moveNumber,
            blackWinRate: closestPoint.blackWinRate,
            x: mouseX,
            y: event.clientY - rect.top,
        };
    } else {
        hoveredPoint.value = null;
    }
}

function handleMouseLeave() {
    hoveredPoint.value = null;
}
</script>

<template>
    <div class="win-rate-graph">
        <div class="graph-header">
            <span class="label">Game Flow</span>
            <span class="current-rate" :class="trend">
                {{ history.length > 0 ? `${history[history.length - 1]?.blackWinRate.toFixed(0)}%` : '--' }}
            </span>
        </div>
        <div
            ref="containerRef"
            class="graph-container"
            @click="handleClick"
            @mousemove="handleMouseMove"
            @mouseleave="handleMouseLeave"
        >
            <svg
                :viewBox="`0 0 100 ${height}`"
                preserveAspectRatio="none"
                class="graph-svg"
            >
                <!-- Gradient definitions -->
                <defs>
                    <linearGradient id="blackGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#1a1a1a;stop-opacity:0.4" />
                        <stop offset="100%" style="stop-color:#1a1a1a;stop-opacity:0.1" />
                    </linearGradient>
                    <linearGradient id="whiteGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#888888;stop-opacity:0.1" />
                        <stop offset="100%" style="stop-color:#888888;stop-opacity:0.3" />
                    </linearGradient>
                </defs>

                <!-- Background zones - subtle -->
                <rect
                    :x="padding.left"
                    :y="padding.top"
                    :width="graphWidth"
                    :height="graphHeight / 2"
                    class="zone black-zone"
                />
                <rect
                    :x="padding.left"
                    :y="midLineY"
                    :width="graphWidth"
                    :height="graphHeight / 2"
                    class="zone white-zone"
                />

                <!-- 50% center line -->
                <line
                    :x1="padding.left"
                    :y1="midLineY"
                    :x2="100 - padding.right"
                    :y2="midLineY"
                    class="center-line"
                />

                <!-- Grid lines for 25% and 75% -->
                <line
                    :x1="padding.left"
                    :y1="yScale(75)"
                    :x2="100 - padding.right"
                    :y2="yScale(75)"
                    class="grid-line"
                />
                <line
                    :x1="padding.left"
                    :y1="yScale(25)"
                    :x2="100 - padding.right"
                    :y2="yScale(25)"
                    class="grid-line"
                />

                <!-- Area fills - split by advantage -->
                <path
                    v-if="history.length > 0"
                    :d="blackAreaPath"
                    class="area-fill-black"
                />
                <path
                    v-if="history.length > 0"
                    :d="whiteAreaPath"
                    class="area-fill-white"
                />

                <!-- Win rate line -->
                <path
                    v-if="history.length > 0"
                    :d="linePath"
                    class="rate-line"
                />

                <!-- Data points -->
                <g v-if="history.length > 0 && history.length < 50" class="data-points">
                    <circle
                        v-for="point in history"
                        :key="point.moveNumber"
                        :cx="xScale(point.moveNumber)"
                        :cy="yScale(point.blackWinRate)"
                        r="1.5"
                        class="data-point"
                    />
                </g>

                <!-- Current position marker -->
                <g v-if="currentPosition">
                    <line
                        :x1="currentPosition.x"
                        :y1="padding.top"
                        :x2="currentPosition.x"
                        :y2="height - padding.bottom"
                        class="position-line"
                    />
                    <circle
                        :cx="currentPosition.x"
                        :cy="currentPosition.y"
                        r="4"
                        class="position-marker-outer"
                    />
                    <circle
                        :cx="currentPosition.x"
                        :cy="currentPosition.y"
                        r="2.5"
                        class="position-marker"
                    />
                </g>
            </svg>

            <!-- Axis labels -->
            <div class="axis-labels">
                <span class="label-black">Black</span>
                <span class="label-white">White</span>
            </div>

            <!-- Hover tooltip -->
            <div
                v-if="hoveredPoint"
                class="graph-tooltip"
                :style="{ left: `${hoveredPoint.x}px`, top: `${hoveredPoint.y - 40}px` }"
            >
                <div class="tooltip-content">
                    <span class="tooltip-move">Move {{ hoveredPoint.moveNumber }}</span>
                    <span class="tooltip-rate">Black: {{ hoveredPoint.blackWinRate.toFixed(1) }}%</span>
                </div>
            </div>
        </div>
        <div v-if="history.length === 0" class="empty-state">
            Play moves to see win rate trend
        </div>
    </div>
</template>

<style scoped>
.win-rate-graph {
    position: relative;
    background-color: var(--card);
    border-radius: 0.5rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.graph-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.625rem 1rem;
    background-color: var(--background);
}

.graph-header .label {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.current-rate {
    font-size: 1rem;
    font-weight: 700;
    font-family: monospace;
}

.current-rate.black {
    color: #1a1a1a;
}

:root.dark .current-rate.black {
    color: #e5e5e5;
}

.current-rate.white {
    color: #666;
}

.current-rate.even {
    color: var(--muted-foreground);
}

.graph-container {
    position: relative;
    height: 140px;
    cursor: crosshair;
    background: linear-gradient(180deg, rgba(0,0,0,0.02) 0%, transparent 50%, rgba(255,255,255,0.02) 100%);
}

.graph-svg {
    width: 100%;
    height: 100%;
}

.zone {
    opacity: 0.03;
}

.zone.black-zone {
    fill: #1a1a1a;
}

.zone.white-zone {
    fill: #888888;
}

.center-line {
    stroke: var(--border);
    stroke-width: 0.5;
    opacity: 0.5;
}

.grid-line {
    stroke: var(--border);
    stroke-width: 0.3;
    stroke-dasharray: 2 4;
    opacity: 0.3;
}

/* Area fills - colored by advantage */
.area-fill-black {
    fill: url(#blackGradient);
}

.area-fill-white {
    fill: url(#whiteGradient);
}

.rate-line {
    fill: none;
    stroke: #374151;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
    vector-effect: non-scaling-stroke;
}

:root.dark .rate-line {
    stroke: #9ca3af;
}

.data-point {
    fill: var(--foreground);
    opacity: 0.4;
}

.position-marker-outer {
    fill: white;
    stroke: var(--go-green);
    stroke-width: 1.5;
}

.position-marker {
    fill: var(--go-green);
}

.position-line {
    stroke: var(--go-green);
    stroke-width: 1;
    opacity: 0.6;
}

.axis-labels {
    position: absolute;
    top: 0;
    bottom: 0;
    right: 8px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 16px 0;
    pointer-events: none;
}

.axis-labels span {
    font-size: 0.625rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    opacity: 0.6;
}

.label-black {
    color: #1a1a1a;
}

:root.dark .label-black {
    color: #e5e5e5;
}

.label-white {
    color: #666;
}

.empty-state {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8125rem;
    color: var(--muted-foreground);
    background-color: var(--card);
}

/* Hover tooltip */
.graph-tooltip {
    position: absolute;
    transform: translateX(-50%);
    z-index: 10;
    pointer-events: none;
}

.tooltip-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    padding: 6px 10px;
    background-color: var(--popover);
    border: 1px solid var(--border);
    border-radius: 6px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    white-space: nowrap;
}

.tooltip-move {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--foreground);
}

.tooltip-rate {
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--muted-foreground);
}
</style>
