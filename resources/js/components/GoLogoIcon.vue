<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        :class="className"
        v-bind="$attrs"
    >
        <!-- Go board grid pattern -->
        <rect x="2" y="2" width="36" height="36" rx="2" fill="currentColor" opacity="0.15" />

        <!-- Grid lines -->
        <g stroke="currentColor" stroke-width="0.75" opacity="0.4">
            <!-- Vertical lines -->
            <line x1="10" y1="6" x2="10" y2="34" />
            <line x1="20" y1="6" x2="20" y2="34" />
            <line x1="30" y1="6" x2="30" y2="34" />
            <!-- Horizontal lines -->
            <line x1="6" y1="10" x2="34" y2="10" />
            <line x1="6" y1="20" x2="34" y2="20" />
            <line x1="6" y1="30" x2="34" y2="30" />
        </g>

        <!-- Black stone -->
        <circle cx="10" cy="10" r="5" fill="#1a1a1a" />
        <circle cx="8" cy="8" r="1.5" fill="#4a4a4a" opacity="0.6" />

        <!-- White stone -->
        <circle cx="30" cy="30" r="5" fill="#f5f5f5" stroke="#ddd" stroke-width="0.5" />
        <circle cx="28" cy="28" r="1.5" fill="#fff" opacity="0.8" />

        <!-- Another black stone -->
        <circle cx="20" cy="20" r="5" fill="#1a1a1a" />
        <circle cx="18" cy="18" r="1.5" fill="#4a4a4a" opacity="0.6" />
    </svg>
</template>
