<script setup lang="ts">
import { Zap, Timer, Clock, Hourglass } from 'lucide-vue-next';
import { computed } from 'vue';

export interface TimeControl {
    id: string;
    name: string;
    time: number; // seconds
    label: string;
    icon: typeof Zap;
}

const timeControls: TimeControl[] = [
    { id: 'bullet', name: 'Bullet', time: 60, label: '1 min', icon: Zap },
    { id: 'blitz', name: 'Blitz', time: 300, label: '5 min', icon: Timer },
    { id: 'rapid', name: 'Rapid', time: 900, label: '15 min', icon: Clock },
    { id: 'classical', name: 'Classical', time: 1800, label: '30 min', icon: Hourglass },
];

const props = defineProps<{
    modelValue: string;
}>();

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void;
}>();

const selectedControl = computed(() => {
    return timeControls.find(tc => tc.id === props.modelValue) ?? timeControls[1];
});

function selectTimeControl(id: string) {
    emit('update:modelValue', id);
}
</script>

<template>
    <div class="time-control-selector">
        <h3 class="selector-title">Time Control</h3>
        <div class="time-controls">
            <button
                v-for="control in timeControls"
                :key="control.id"
                class="time-card"
                :class="{ selected: modelValue === control.id }"
                @click="selectTimeControl(control.id)"
            >
                <component :is="control.icon" :size="20" class="time-icon" />
                <span class="time-name">{{ control.name }}</span>
                <span class="time-label">{{ control.label }}</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.time-control-selector {
    width: 100%;
}

.selector-title {
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    margin: 0 0 1rem;
    text-align: center;
}

.time-controls {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 0.5rem;
}

@media (max-width: 480px) {
    .time-controls {
        grid-template-columns: repeat(2, 1fr);
    }
}

.time-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.375rem;
    padding: 0.875rem 0.5rem;
    background-color: var(--card);
    border: 2px solid var(--border);
    border-radius: 0.75rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.time-card:hover {
    border-color: var(--go-green);
}

.time-card.selected {
    border-color: var(--go-green);
    background-color: var(--go-green-muted);
}

.time-icon {
    color: var(--muted-foreground);
}

.time-card.selected .time-icon {
    color: var(--go-green);
}

.time-name {
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--foreground);
}

.time-label {
    font-size: 0.6875rem;
    color: var(--muted-foreground);
}
</style>
