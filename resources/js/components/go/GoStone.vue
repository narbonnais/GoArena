<script setup lang="ts">
import type { Stone } from '@/types/go';

const props = defineProps<{
    color: Stone;
    x: number;
    y: number;
    cellSize: number;
    isLastMove?: boolean;
}>();

const stoneRadius = props.cellSize * 0.45;
</script>

<template>
    <g class="go-stone" :class="[color, { 'last-move': isLastMove }]">
        <!-- Stone shadow -->
        <circle :cx="x + 2" :cy="y + 2" :r="stoneRadius" class="stone-shadow" fill="rgba(0, 0, 0, 0.3)" />

        <!-- Stone body -->
        <circle :cx="x" :cy="y" :r="stoneRadius" :class="`stone-${color}`" />

        <!-- Highlight/reflection for 3D effect -->
        <ellipse
            v-if="color === 'white'"
            :cx="x - stoneRadius * 0.25"
            :cy="y - stoneRadius * 0.25"
            :rx="stoneRadius * 0.3"
            :ry="stoneRadius * 0.2"
            fill="rgba(255, 255, 255, 0.6)"
        />
        <ellipse
            v-else
            :cx="x - stoneRadius * 0.25"
            :cy="y - stoneRadius * 0.25"
            :rx="stoneRadius * 0.25"
            :ry="stoneRadius * 0.15"
            fill="rgba(255, 255, 255, 0.15)"
        />

        <!-- Last move marker -->
        <circle
            v-if="isLastMove"
            :cx="x"
            :cy="y"
            :r="stoneRadius * 0.35"
            fill="none"
            :stroke="color === 'black' ? '#fff' : '#000'"
            stroke-width="2"
        />
    </g>
</template>

<style scoped>
.go-stone {
    transition: opacity 0.15s ease-in-out;
}

.stone-black {
    fill: #1a1a1a;
    stroke: #000;
    stroke-width: 1;
}

.stone-white {
    fill: #f5f5f5;
    stroke: #ccc;
    stroke-width: 1;
}

.stone-shadow {
    filter: blur(2px);
}
</style>
