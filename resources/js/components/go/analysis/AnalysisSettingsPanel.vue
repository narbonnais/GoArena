<script setup lang="ts">
import { Settings2 } from 'lucide-vue-next';

import type { AnalysisSettings, AnalysisPreset } from '@/composables/go/useAnalysisSettings';

const props = defineProps<{
    settings: AnalysisSettings;
    currentPreset: string | null;
    presets: AnalysisPreset[];
}>();

const emit = defineEmits<{
    (e: 'apply-preset', name: string): void;
    (e: 'update:auto-analyze', value: boolean): void;
}>();
</script>

<template>
    <div class="settings-panel">
        <div class="panel-header">
            <div class="header-left">
                <Settings2 :size="14" />
                <span class="panel-title">Analysis Power</span>
            </div>
            <button
                class="toggle-btn"
                :class="{ active: settings.autoAnalyze }"
                @click="emit('update:auto-analyze', !settings.autoAnalyze)"
            >
                {{ settings.autoAnalyze ? 'ON' : 'OFF' }}
            </button>
        </div>

        <!-- Presets Row -->
        <div class="presets-row">
            <button
                v-for="preset in presets"
                :key="preset.name"
                class="preset-btn"
                :class="{ active: currentPreset === preset.name }"
                :disabled="!settings.autoAnalyze"
                @click="emit('apply-preset', preset.name)"
            >
                {{ preset.label }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.settings-panel {
    padding: 0.75rem;
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.header-left {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.panel-title {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted-foreground);
}

/* Toggle */
.toggle-btn {
    padding: 0.25rem 0.75rem;
    font-size: 0.75rem;
    font-weight: 600;
    border-radius: 1rem;
    cursor: pointer;
    transition: all 0.15s ease;
    background-color: var(--muted);
    color: var(--muted-foreground);
    border: 1px solid var(--border);
}

.toggle-btn:hover {
    border-color: var(--foreground);
}

.toggle-btn.active {
    background-color: var(--go-green);
    color: white;
    border-color: var(--go-green);
}

/* Presets */
.presets-row {
    display: flex;
    gap: 0.25rem;
    margin-top: 0.5rem;
}

.preset-btn {
    flex: 1;
    padding: 0.375rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 500;
    color: var(--foreground);
    background-color: var(--muted);
    border: 1px solid var(--border);
    border-radius: 0.375rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.preset-btn:hover:not(:disabled) {
    background-color: var(--accent);
    border-color: var(--foreground);
}

.preset-btn:disabled {
    opacity: 0.4;
    cursor: not-allowed;
}

.preset-btn.active {
    background-color: var(--go-green);
    color: white;
    border-color: var(--go-green);
}
</style>
