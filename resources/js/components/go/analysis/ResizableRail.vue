<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue';

const props = withDefaults(
    defineProps<{
        minWidth?: number;
        maxWidth?: number;
        initialWidth?: number;
        collapsed?: boolean;
    }>(),
    {
        minWidth: 280,
        maxWidth: 600,
        initialWidth: 360,
        collapsed: false,
    }
);

const emit = defineEmits<{
    (e: 'resize', width: number): void;
    (e: 'collapse-change', collapsed: boolean): void;
}>();

const railWidth = ref(props.initialWidth);
const isResizing = ref(false);
const startX = ref(0);
const startWidth = ref(0);

function startResize(event: MouseEvent) {
    isResizing.value = true;
    startX.value = event.clientX;
    startWidth.value = railWidth.value;

    document.addEventListener('mousemove', handleResize);
    document.addEventListener('mouseup', stopResize);
    document.body.style.cursor = 'col-resize';
    document.body.style.userSelect = 'none';
}

function handleResize(event: MouseEvent) {
    if (!isResizing.value) return;

    const delta = startX.value - event.clientX;
    let newWidth = startWidth.value + delta;

    // Clamp to min/max
    newWidth = Math.max(props.minWidth, Math.min(props.maxWidth, newWidth));

    railWidth.value = newWidth;
    emit('resize', newWidth);
}

function stopResize() {
    isResizing.value = false;
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
    document.body.style.cursor = '';
    document.body.style.userSelect = '';
}

onUnmounted(() => {
    document.removeEventListener('mousemove', handleResize);
    document.removeEventListener('mouseup', stopResize);
});
</script>

<template>
    <aside
        class="resizable-rail"
        :class="{ collapsed: collapsed, resizing: isResizing }"
        :style="{ width: collapsed ? '0px' : `${railWidth}px` }"
    >
        <!-- Resize handle -->
        <div
            v-if="!collapsed"
            class="resize-handle"
            @mousedown="startResize"
        >
            <div class="handle-bar"></div>
        </div>

        <!-- Rail content -->
        <div v-if="!collapsed" class="rail-content">
            <slot></slot>
        </div>
    </aside>
</template>

<style scoped>
.resizable-rail {
    position: relative;
    display: flex;
    flex-direction: column;
    flex-shrink: 0;
    transition: width 0.2s ease;
    overflow: hidden;
}

.resizable-rail.resizing {
    transition: none;
}

.resizable-rail.collapsed {
    width: 0 !important;
}

.resize-handle {
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 8px;
    cursor: col-resize;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
}

.resize-handle:hover .handle-bar,
.resizing .handle-bar {
    opacity: 1;
    background-color: var(--accent-analyze);
}

.handle-bar {
    width: 3px;
    height: 40px;
    background-color: var(--border);
    border-radius: 2px;
    opacity: 0.5;
    transition: all 0.15s ease;
}

.rail-content {
    flex: 1;
    overflow-y: auto;
    padding-left: 8px;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Hide on mobile */
@media (max-width: 1200px) {
    .resizable-rail {
        display: none;
    }
}
</style>
