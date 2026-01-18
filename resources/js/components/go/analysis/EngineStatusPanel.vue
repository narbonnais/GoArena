<script setup lang="ts">
import { Cpu, Loader2 } from 'lucide-vue-next';

defineProps<{
    isAnalyzing: boolean;
    engineName?: string;
    depth?: number;
    visits?: number;
}>();
</script>

<template>
    <div class="engine-status">
        <div class="status-header">
            <div class="engine-info">
                <Cpu :size="14" class="engine-icon" />
                <span class="engine-name">{{ engineName || 'KataGo' }}</span>
            </div>
            <div class="status-indicator" :class="{ active: isAnalyzing }">
                <Loader2 v-if="isAnalyzing" :size="12" class="spin" />
                <span v-else class="dot"></span>
                <span class="status-text">{{ isAnalyzing ? 'Analyzing' : 'Ready' }}</span>
            </div>
        </div>
        <div v-if="depth || visits" class="status-stats">
            <span v-if="depth" class="stat">
                <span class="stat-label">Depth</span>
                <span class="stat-value font-data">{{ depth }}</span>
            </span>
            <span v-if="visits" class="stat">
                <span class="stat-label">Visits</span>
                <span class="stat-value font-data">{{ visits.toLocaleString() }}</span>
            </span>
        </div>
    </div>
</template>

<style scoped>
.engine-status {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
    padding: 0.625rem 0.75rem;
    background-color: var(--card);
    border-radius: 0.5rem;
    font-size: 0.75rem;
}

.status-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.engine-info {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: var(--muted-foreground);
}

.engine-icon {
    color: var(--accent-analyze);
}

.engine-name {
    font-weight: 500;
}

.status-indicator {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: var(--muted-foreground);
}

.status-indicator.active {
    color: var(--accent-analyze);
}

.dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: var(--muted-foreground);
}

.status-indicator.active .dot {
    background-color: var(--accent-analyze);
}

.status-text {
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}

.status-stats {
    display: flex;
    gap: 1rem;
}

.stat {
    display: flex;
    gap: 0.25rem;
}

.stat-label {
    color: var(--muted-foreground);
}

.stat-value {
    color: var(--foreground);
    font-weight: 500;
}

.spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from {
        transform: rotate(0deg);
    }
    to {
        transform: rotate(360deg);
    }
}
</style>
