<script setup lang="ts">
import { Save, FilePlus, Loader2, Check } from 'lucide-vue-next';

const props = defineProps<{
    title: string;
    isSaving: boolean;
    hasUnsavedChanges: boolean;
    isNew: boolean;
}>();

const emit = defineEmits<{
    (e: 'update:title', title: string): void;
    (e: 'save'): void;
    (e: 'new'): void;
}>();

function handleTitleChange(event: Event) {
    const target = event.target as HTMLInputElement;
    emit('update:title', target.value);
}
</script>

<template>
    <div class="analysis-toolbar">
        <!-- Title Input -->
        <div class="title-section">
            <input
                type="text"
                class="title-input"
                :value="title"
                placeholder="Study title..."
                @input="handleTitleChange"
                @keydown.enter="emit('save')"
            />
            <span v-if="hasUnsavedChanges" class="unsaved-indicator">
                Unsaved changes
            </span>
            <span v-else-if="!isNew" class="saved-indicator">
                <Check :size="14" />
                Saved
            </span>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <button
                class="toolbar-btn secondary"
                title="New Analysis"
                @click="emit('new')"
            >
                <FilePlus :size="18" />
                <span class="btn-label">New</span>
            </button>

            <button
                class="toolbar-btn primary"
                :disabled="isSaving"
                :title="isNew ? 'Save Study' : 'Save Changes'"
                @click="emit('save')"
            >
                <Loader2 v-if="isSaving" :size="18" class="spinner" />
                <Save v-else :size="18" />
                <span class="btn-label">{{ isNew ? 'Save' : 'Save' }}</span>
            </button>
        </div>
    </div>
</template>

<style scoped>
.analysis-toolbar {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
    min-width: 0;
}

.title-section {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    min-width: 0;
}

.title-input {
    flex: 1;
    min-width: 0;
    max-width: 300px;
    padding: 0.5rem 0.75rem;
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--foreground);
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    transition: border-color 0.15s ease;
}

.title-input:focus {
    outline: none;
    border-color: var(--ring);
}

.title-input::placeholder {
    color: var(--muted-foreground);
    font-weight: 400;
}

.unsaved-indicator {
    font-size: 0.75rem;
    color: #f59e0b;
    white-space: nowrap;
}

.saved-indicator {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.75rem;
    color: #22c55e;
    white-space: nowrap;
}

.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-shrink: 0;
}

.toolbar-btn {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--foreground);
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.toolbar-btn:hover:not(:disabled) {
    background-color: var(--accent);
    border-color: var(--foreground);
}

.toolbar-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.toolbar-btn.primary {
    background-color: var(--go-green);
    border-color: var(--go-green);
    color: white;
}

.toolbar-btn.primary:hover:not(:disabled) {
    background-color: var(--go-green-hover);
    border-color: var(--go-green-hover);
}

.toolbar-btn.secondary {
    background-color: var(--muted);
}

.btn-label {
    display: none;
}

@media (min-width: 640px) {
    .btn-label {
        display: inline;
    }
}

.spinner {
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
