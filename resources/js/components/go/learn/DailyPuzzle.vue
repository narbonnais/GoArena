<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Puzzle, ArrowRight } from 'lucide-vue-next';

// Mock puzzle data
const puzzle = {
    difficulty: 'Easy',
    hint: 'Find the best move for Black',
};

// Mini board pattern (5x5 preview)
const boardPattern = [
    [null, null, 'white', null, null],
    [null, 'black', 'white', 'black', null],
    ['white', 'white', null, 'black', null],
    [null, 'black', 'black', null, null],
    [null, null, null, null, null],
];
</script>

<template>
    <div class="daily-puzzle">
        <div class="puzzle-header">
            <div class="puzzle-title-row">
                <Puzzle :size="18" class="puzzle-icon" />
                <span class="puzzle-label">Today's Puzzle</span>
            </div>
            <span class="difficulty-badge">{{ puzzle.difficulty }}</span>
        </div>

        <div class="puzzle-board">
            <svg viewBox="0 0 100 100" class="mini-board">
                <!-- Board background -->
                <rect x="0" y="0" width="100" height="100" fill="#ddb06d" rx="4" />

                <!-- Grid lines -->
                <g stroke="#5c4a32" stroke-width="0.5" opacity="0.6">
                    <line v-for="i in 5" :key="`v-${i}`" :x1="10 + (i-1) * 20" y1="10" :x2="10 + (i-1) * 20" y2="90" />
                    <line v-for="i in 5" :key="`h-${i}`" :x1="10" :y1="10 + (i-1) * 20" :x2="90" :y2="10 + (i-1) * 20" />
                </g>

                <!-- Stones -->
                <g v-for="(row, rowIndex) in boardPattern" :key="`row-${rowIndex}`">
                    <template v-for="(cell, colIndex) in row" :key="`cell-${rowIndex}-${colIndex}`">
                        <circle
                            v-if="cell === 'black'"
                            :cx="10 + colIndex * 20"
                            :cy="10 + rowIndex * 20"
                            r="8"
                            fill="#1a1a1a"
                        />
                        <circle
                            v-if="cell === 'black'"
                            :cx="8 + colIndex * 20"
                            :cy="8 + rowIndex * 20"
                            r="2"
                            fill="#4a4a4a"
                            opacity="0.5"
                        />
                        <circle
                            v-if="cell === 'white'"
                            :cx="10 + colIndex * 20"
                            :cy="10 + rowIndex * 20"
                            r="8"
                            fill="#f5f5f5"
                            stroke="#ddd"
                            stroke-width="0.3"
                        />
                        <circle
                            v-if="cell === 'white'"
                            :cx="8 + colIndex * 20"
                            :cy="8 + rowIndex * 20"
                            r="2"
                            fill="#fff"
                            opacity="0.7"
                        />
                    </template>
                </g>
            </svg>
        </div>

        <p class="puzzle-hint">{{ puzzle.hint }}</p>

        <Link href="/go/learn" class="solve-btn">
            Solve
            <ArrowRight :size="16" />
        </Link>
    </div>
</template>

<style scoped>
.daily-puzzle {
    padding: 1rem 1.25rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
}

.puzzle-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.75rem;
}

.puzzle-title-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.puzzle-icon {
    color: var(--go-green);
}

.puzzle-label {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.difficulty-badge {
    font-size: 0.6875rem;
    font-weight: 600;
    color: var(--go-green);
    background-color: var(--go-green-muted);
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
}

.puzzle-board {
    display: flex;
    justify-content: center;
    margin-bottom: 0.75rem;
}

.mini-board {
    width: 140px;
    height: 140px;
    border-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.puzzle-hint {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    text-align: center;
    margin: 0 0 0.75rem;
}

.solve-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.625rem 1rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: white;
    background-color: var(--go-green);
    border: none;
    border-radius: 0.5rem;
    text-decoration: none;
    transition: all 0.15s ease;
}

.solve-btn:hover {
    background-color: var(--go-green-hover);
}
</style>
