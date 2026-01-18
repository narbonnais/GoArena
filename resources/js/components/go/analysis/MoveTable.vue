<script setup lang="ts">
import { computed, ref, watch, nextTick } from 'vue';

import type { MoveNode, MoveSymbol } from '@/types/analysis';
import type { Coordinate } from '@/types/go';

interface MoveWithAnalysis {
    nodeId: string;
    moveNumber: number;
    stone: 'black' | 'white';
    coordinate: Coordinate | null;
    comment?: string;
    symbols: MoveSymbol[];
    winRateBefore?: number;
    winRateAfter?: number;
    isVariation?: boolean;
}

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

const tableRef = ref<HTMLElement | null>(null);

// Symbol display
const SYMBOLS: Record<MoveSymbol, string> = {
    good: '!',
    bad: '?',
    blunder: '??',
};

// Coordinate to notation (Q11, K7, etc.)
function coordToNotation(coord: Coordinate | null): string {
    if (!coord) return 'Pass';
    const letters = 'ABCDEFGHJKLMNOPQRST';
    return `${letters[coord.x]}${props.boardSize - coord.y}`;
}

// Build flat list of moves (main line only for now)
const moves = computed<MoveWithAnalysis[]>(() => {
    const result: MoveWithAnalysis[] = [];

    function traverse(nodeId: string) {
        const node = props.nodes[nodeId];
        if (!node) return;

        if (node.id !== props.rootId && node.moveNumber > 0) {
            const moveData: MoveWithAnalysis = {
                nodeId: node.id,
                moveNumber: node.moveNumber,
                stone: node.stone,
                coordinate: node.coordinate,
                comment: node.comment,
                symbols: node.symbols,
            };

            // Add win rate data if available
            if (props.winRateHistory) {
                const afterData = props.winRateHistory.find(h => h.moveNumber === node.moveNumber);
                const beforeData = props.winRateHistory.find(h => h.moveNumber === node.moveNumber - 1);

                if (afterData) {
                    moveData.winRateAfter = node.stone === 'black' ? afterData.blackWinRate : 100 - afterData.blackWinRate;
                }
                if (beforeData) {
                    moveData.winRateBefore = node.stone === 'black' ? beforeData.blackWinRate : 100 - beforeData.blackWinRate;
                }
            }

            result.push(moveData);
        }

        // Follow main line (first child)
        if (node.children.length > 0) {
            traverse(node.children[0]);
        }
    }

    traverse(props.rootId);
    return result;
});

// Group moves into pairs (Black/White per row)
const moveRows = computed(() => {
    const rows: { turn: number; black?: MoveWithAnalysis; white?: MoveWithAnalysis }[] = [];

    for (const move of moves.value) {
        const turn = Math.ceil(move.moveNumber / 2);
        let row = rows.find(r => r.turn === turn);

        if (!row) {
            row = { turn };
            rows.push(row);
        }

        if (move.stone === 'black') {
            row.black = move;
        } else {
            row.white = move;
        }
    }

    return rows;
});

// Calculate point swing for a move
function getPointSwing(move: MoveWithAnalysis): number | null {
    if (move.winRateBefore === undefined || move.winRateAfter === undefined) {
        return null;
    }
    // Positive = good for the player, negative = bad
    return move.winRateAfter - move.winRateBefore;
}

// Determine move quality class based on point swing
function getMoveQuality(swing: number | null): string {
    if (swing === null) return '';
    if (swing >= 5) return 'excellent';
    if (swing >= 0) return 'good';
    if (swing >= -5) return 'inaccuracy';
    if (swing >= -15) return 'mistake';
    return 'blunder';
}

// Auto-scroll to current move
watch(() => props.currentNodeId, async () => {
    await nextTick();
    const currentEl = tableRef.value?.querySelector('.current');
    if (currentEl) {
        currentEl.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }
});

// Count total moves
const totalMoves = computed(() => moves.value.length);
</script>

<template>
    <div class="move-table">
        <div class="table-header">
            <span class="header-title">Moves</span>
            <span class="move-count">{{ totalMoves }} moves</span>
        </div>
        <div ref="tableRef" class="table-content">
            <table v-if="moveRows.length > 0">
                <thead>
                    <tr>
                        <th class="col-turn">#</th>
                        <th class="col-black">Black</th>
                        <th class="col-eval">+/-</th>
                        <th class="col-white">White</th>
                        <th class="col-eval">+/-</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in moveRows" :key="row.turn">
                        <td class="col-turn">{{ row.turn }}</td>

                        <!-- Black move -->
                        <td
                            v-if="row.black"
                            class="col-move black"
                            :class="{ current: row.black.nodeId === currentNodeId }"
                            @click="emit('select-node', row.black.nodeId)"
                            @mouseenter="emit('hover-move', row.black.coordinate)"
                            @mouseleave="emit('hover-move', null)"
                        >
                            <span class="stone-dot black"></span>
                            <span class="coord">{{ coordToNotation(row.black.coordinate) }}</span>
                            <span v-if="row.black.symbols.length" class="symbols">
                                {{ row.black.symbols.map(s => SYMBOLS[s]).join('') }}
                            </span>
                        </td>
                        <td v-else class="col-move empty">-</td>

                        <!-- Black eval -->
                        <td
                            v-if="row.black"
                            class="col-eval"
                            :class="getMoveQuality(getPointSwing(row.black))"
                        >
                            <template v-if="getPointSwing(row.black) !== null">
                                {{ getPointSwing(row.black)! >= 0 ? '+' : '' }}{{ getPointSwing(row.black)!.toFixed(0) }}
                            </template>
                        </td>
                        <td v-else class="col-eval"></td>

                        <!-- White move -->
                        <td
                            v-if="row.white"
                            class="col-move white"
                            :class="{ current: row.white.nodeId === currentNodeId }"
                            @click="emit('select-node', row.white.nodeId)"
                            @mouseenter="emit('hover-move', row.white.coordinate)"
                            @mouseleave="emit('hover-move', null)"
                        >
                            <span class="stone-dot white"></span>
                            <span class="coord">{{ coordToNotation(row.white.coordinate) }}</span>
                            <span v-if="row.white.symbols.length" class="symbols">
                                {{ row.white.symbols.map(s => SYMBOLS[s]).join('') }}
                            </span>
                        </td>
                        <td v-else class="col-move empty">-</td>

                        <!-- White eval -->
                        <td
                            v-if="row.white"
                            class="col-eval"
                            :class="getMoveQuality(getPointSwing(row.white))"
                        >
                            <template v-if="getPointSwing(row.white) !== null">
                                {{ getPointSwing(row.white)! >= 0 ? '+' : '' }}{{ getPointSwing(row.white)!.toFixed(0) }}
                            </template>
                        </td>
                        <td v-else class="col-eval"></td>
                    </tr>
                </tbody>
            </table>
            <div v-else class="empty-state">
                Play moves on the board to begin
            </div>
        </div>
    </div>
</template>

<style scoped>
.move-table {
    display: flex;
    flex-direction: column;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    overflow: hidden;
    flex: 1;
    min-height: 200px;
}

.table-header {
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

.move-count {
    font-size: 0.7rem;
    color: var(--muted-foreground);
}

.table-content {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
}

table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.8rem;
}

thead {
    position: sticky;
    top: 0;
    background-color: var(--background);
    z-index: 1;
}

th {
    padding: 0.375rem 0.25rem;
    font-size: 0.65rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    text-align: left;
    border-bottom: 1px solid var(--border);
}

td {
    padding: 0.375rem 0.25rem;
    border-bottom: 1px solid var(--border);
    vertical-align: middle;
}

tr:last-child td {
    border-bottom: none;
}

.col-turn {
    width: 28px;
    text-align: center;
    color: var(--muted-foreground);
    font-size: 0.7rem;
}

.col-move {
    cursor: pointer;
    transition: background-color 0.1s ease;
    white-space: nowrap;
}

.col-move:hover {
    background-color: var(--accent);
}

.col-move.current {
    background-color: var(--go-green);
    color: white;
}

.col-move.current .coord {
    color: white;
}

.col-move.empty {
    color: var(--muted-foreground);
    cursor: default;
}

.col-eval {
    width: 36px;
    text-align: right;
    font-size: 0.7rem;
    font-weight: 500;
    font-family: monospace;
    padding-right: 0.5rem;
}

.col-eval.excellent {
    color: #22c55e;
}

.col-eval.good {
    color: #86efac;
}

.col-eval.inaccuracy {
    color: #fbbf24;
}

.col-eval.mistake {
    color: #f97316;
}

.col-eval.blunder {
    color: #ef4444;
}

.stone-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin-right: 4px;
    vertical-align: middle;
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
    font-weight: 500;
    color: var(--foreground);
}

.symbols {
    color: #f59e0b;
    font-weight: bold;
    margin-left: 2px;
}

.empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    min-height: 100px;
    color: var(--muted-foreground);
    font-size: 0.8rem;
}
</style>
