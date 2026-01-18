<script setup lang="ts">
import { ChevronUp } from 'lucide-vue-next';
import { ref } from 'vue';

const props = withDefaults(
    defineProps<{
        tabs: { id: string; label: string }[];
        defaultTab?: string;
    }>(),
    {
        defaultTab: '',
    }
);

const activeTab = ref(props.defaultTab || props.tabs[0]?.id || '');
const isExpanded = ref(false);

function selectTab(tabId: string) {
    activeTab.value = tabId;
    if (!isExpanded.value) {
        isExpanded.value = true;
    }
}

function toggleExpanded() {
    isExpanded.value = !isExpanded.value;
}
</script>

<template>
    <div class="bottom-drawer" :class="{ expanded: isExpanded }">
        <!-- Drawer Header -->
        <div class="drawer-header">
            <div class="tabs">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    class="tab"
                    :class="{ active: activeTab === tab.id }"
                    @click="selectTab(tab.id)"
                >
                    {{ tab.label }}
                </button>
            </div>
            <button class="expand-btn" @click="toggleExpanded">
                <ChevronUp :size="20" class="chevron" :class="{ flipped: isExpanded }" />
            </button>
        </div>

        <!-- Drawer Content -->
        <div class="drawer-content">
            <slot :name="activeTab"></slot>
        </div>
    </div>
</template>

<style scoped>
.bottom-drawer {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: var(--card);
    border-top: 1px solid var(--border);
    border-radius: 1rem 1rem 0 0;
    z-index: 40;
    transition: transform 0.3s ease;
    transform: translateY(calc(100% - 48px));
}

.bottom-drawer.expanded {
    transform: translateY(0);
}

/* Show only on mobile */
@media (max-width: 1200px) {
    .bottom-drawer {
        display: block;
    }
}

.drawer-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1rem;
    height: 48px;
    border-bottom: 1px solid var(--border);
}

.tabs {
    display: flex;
    gap: 0.5rem;
}

.tab {
    padding: 0.5rem 1rem;
    font-size: 0.8125rem;
    font-weight: 500;
    color: var(--muted-foreground);
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.tab:hover {
    color: var(--foreground);
}

.tab.active {
    color: var(--accent-analyze);
    background-color: rgba(74, 163, 163, 0.1);
}

.expand-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: transparent;
    border: none;
    color: var(--muted-foreground);
    cursor: pointer;
}

.chevron {
    transition: transform 0.2s ease;
}

.chevron.flipped {
    transform: rotate(180deg);
}

.drawer-content {
    height: 300px;
    overflow-y: auto;
    padding: 1rem;
}
</style>
