<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-go-green"
    >
        <AppLogoIcon class="size-6" />
    </div>
    <div class="ml-2 grid flex-1 text-left text-sm">
        <span class="truncate leading-tight font-bold text-foreground"
            >GoArena</span
        >
        <span class="truncate text-xs text-muted-foreground"
            >Master the Ancient Game</span
        >
    </div>
</template>

<style scoped>
.bg-go-green {
    background-color: var(--go-green);
}
</style>
