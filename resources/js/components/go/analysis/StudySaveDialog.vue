<script setup lang="ts">
import { X, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    initialTitle: string;
    initialIsPublic: boolean;
    isSaving: boolean;
}>();

const emit = defineEmits<{
    (e: 'save', data: { title: string; isPublic: boolean }): void;
    (e: 'cancel'): void;
}>();

const title = ref(props.initialTitle || 'New Analysis');
const isPublic = ref(props.initialIsPublic);

function handleSubmit() {
    if (!title.value.trim()) return;

    emit('save', {
        title: title.value.trim(),
        isPublic: isPublic.value,
    });
}

function handleKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape') {
        emit('cancel');
    }
}
</script>

<template>
    <div class="dialog-overlay" @click.self="emit('cancel')" @keydown="handleKeydown">
        <div class="dialog">
            <div class="dialog-header">
                <h2 class="dialog-title">Save Analysis Study</h2>
                <button class="close-btn" @click="emit('cancel')">
                    <X :size="20" />
                </button>
            </div>

            <form class="dialog-body" @submit.prevent="handleSubmit">
                <div class="form-group">
                    <label for="study-title" class="form-label">Title</label>
                    <input
                        id="study-title"
                        v-model="title"
                        type="text"
                        class="form-input"
                        placeholder="Enter study title..."
                        required
                        autofocus
                    />
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input
                            v-model="isPublic"
                            type="checkbox"
                            class="checkbox-input"
                        />
                        <span class="checkbox-text">Make this study public</span>
                    </label>
                    <p class="checkbox-hint">
                        Public studies can be viewed by anyone with the link.
                    </p>
                </div>

                <div class="dialog-actions">
                    <button
                        type="button"
                        class="btn btn-secondary"
                        :disabled="isSaving"
                        @click="emit('cancel')"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="btn btn-primary"
                        :disabled="isSaving || !title.trim()"
                    >
                        <Loader2 v-if="isSaving" :size="16" class="spinner" />
                        <span>{{ isSaving ? 'Saving...' : 'Save Study' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>

<style scoped>
.dialog-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    padding: 1rem;
}

.dialog {
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 1rem;
    max-width: 480px;
    width: 100%;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
}

.dialog-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid var(--border);
}

.dialog-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--foreground);
    margin: 0;
}

.close-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    color: var(--muted-foreground);
    background: transparent;
    border: none;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.close-btn:hover {
    color: var(--foreground);
    background-color: var(--accent);
}

.dialog-body {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--foreground);
}

.form-input {
    padding: 0.75rem;
    font-size: 0.9375rem;
    font-family: inherit;
    color: var(--foreground);
    background-color: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    transition: border-color 0.15s ease;
}

.form-input:focus {
    outline: none;
    border-color: var(--ring);
}

.checkbox-group {
    gap: 0.25rem;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.checkbox-input {
    width: 1rem;
    height: 1rem;
    accent-color: var(--go-green);
    cursor: pointer;
}

.checkbox-text {
    font-size: 0.9375rem;
    color: var(--foreground);
}

.checkbox-hint {
    font-size: 0.8125rem;
    color: var(--muted-foreground);
    margin: 0;
    padding-left: 1.5rem;
}

.dialog-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.75rem;
    margin-top: 0.5rem;
}

.btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 600;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
}

.btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-primary {
    background-color: var(--go-green);
    color: white;
}

.btn-primary:hover:not(:disabled) {
    background-color: var(--go-green-hover);
}

.btn-secondary {
    background-color: var(--muted);
    color: var(--foreground);
}

.btn-secondary:hover:not(:disabled) {
    background-color: var(--accent);
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
