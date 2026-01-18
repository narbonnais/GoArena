<script setup lang="ts">
import { X, Settings } from 'lucide-vue-next';

import { useGameSettings, type BoardTheme } from '@/composables/go/useGameSettings';

const props = defineProps<{
    open: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const { settings, setShowCoordinates, setSoundEffects, setBoardTheme } = useGameSettings();

const boardThemes: { value: BoardTheme; label: string; color: string }[] = [
    { value: 'classic', label: 'Classic', color: '#d7a868' },
    { value: 'kaya', label: 'Kaya', color: '#e8c47c' },
    { value: 'slate', label: 'Slate', color: '#5a6a72' },
];

function handleOverlayClick(event: MouseEvent) {
    if (event.target === event.currentTarget) {
        emit('close');
    }
}
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="open" class="modal-overlay" @click="handleOverlayClick">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="modal-title">
                            <Settings :size="20" />
                            <span>Game Settings</span>
                        </div>
                        <button class="close-btn" @click="emit('close')">
                            <X :size="20" />
                        </button>
                    </div>

                    <div class="modal-body">
                        <div class="setting-item">
                            <div class="setting-info">
                                <span class="setting-label">Show Coordinates</span>
                                <span class="setting-desc">Display board coordinates (A-T, 1-19)</span>
                            </div>
                            <label class="toggle">
                                <input
                                    type="checkbox"
                                    :checked="settings.showCoordinates"
                                    @change="setShowCoordinates(($event.target as HTMLInputElement).checked)"
                                />
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <span class="setting-label">Sound Effects</span>
                                <span class="setting-desc">Play sounds for moves and captures</span>
                            </div>
                            <label class="toggle">
                                <input
                                    type="checkbox"
                                    :checked="settings.soundEffects"
                                    @change="setSoundEffects(($event.target as HTMLInputElement).checked)"
                                />
                                <span class="toggle-slider"></span>
                            </label>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <span class="setting-label">Board Theme</span>
                                <span class="setting-desc">Change board appearance</span>
                            </div>
                            <div class="theme-options">
                                <button
                                    v-for="theme in boardThemes"
                                    :key="theme.value"
                                    class="theme-option"
                                    :class="{ selected: settings.boardTheme === theme.value }"
                                    :style="{ '--theme-color': theme.color }"
                                    @click="setBoardTheme(theme.value)"
                                >
                                    <span class="theme-swatch"></span>
                                    <span class="theme-label">{{ theme.label }}</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    padding: 1rem;
}

.modal-content {
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    width: 100%;
    max-width: 400px;
    overflow: hidden;
}

.modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
}

.modal-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
}

.close-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    background: transparent;
    border: none;
    border-radius: 0.375rem;
    color: var(--muted-foreground);
    cursor: pointer;
    transition: all 0.15s ease;
}

.close-btn:hover {
    background-color: var(--accent);
    color: var(--foreground);
}

.modal-body {
    padding: 1rem 1.25rem;
}

.setting-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid var(--border);
}

.setting-item:last-child {
    border-bottom: none;
}

.setting-item.disabled {
    opacity: 0.5;
}

.setting-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.setting-label {
    font-size: 0.9375rem;
    font-weight: 500;
    color: var(--foreground);
}

.setting-desc {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

/* Theme Options */
.theme-options {
    display: flex;
    gap: 0.5rem;
}

.theme-option {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem;
    background: transparent;
    border: 2px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.theme-option:hover {
    border-color: var(--muted-foreground);
}

.theme-option.selected {
    border-color: var(--go-green);
}

.theme-swatch {
    width: 24px;
    height: 24px;
    border-radius: 4px;
    background-color: var(--theme-color);
    box-shadow: inset 0 -1px 2px rgba(0, 0, 0, 0.2);
}

.theme-label {
    font-size: 0.625rem;
    font-weight: 500;
    color: var(--muted-foreground);
    text-transform: uppercase;
}

.theme-option.selected .theme-label {
    color: var(--go-green);
}

/* Toggle Switch */
.toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    inset: 0;
    background-color: var(--border);
    border-radius: 24px;
    transition: all 0.2s ease;
}

.toggle-slider::before {
    position: absolute;
    content: '';
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    border-radius: 50%;
    transition: all 0.2s ease;
}

.toggle input:checked + .toggle-slider {
    background-color: var(--go-green);
}

.toggle input:checked + .toggle-slider::before {
    transform: translateX(20px);
}

/* Transitions */
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-active .modal-content,
.modal-leave-active .modal-content {
    transition: transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from .modal-content,
.modal-leave-to .modal-content {
    transform: scale(0.95);
}
</style>
