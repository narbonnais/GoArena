<script setup lang="ts">
import { Check, X, Star } from 'lucide-vue-next';
import { computed, watch, ref } from 'vue';

const props = defineProps<{
    type: 'correct' | 'incorrect' | 'complete' | null;
    message?: string;
    autoHide?: boolean;
    autoHideDelay?: number;
}>();

const emit = defineEmits<{
    (e: 'hidden'): void;
}>();

const isVisible = ref(false);

const defaultMessages = {
    correct: 'Correct!',
    incorrect: 'Try again!',
    complete: 'Lesson Complete!',
};

const displayMessage = computed(() => {
    if (props.message) return props.message;
    if (props.type) return defaultMessages[props.type];
    return '';
});

watch(
    () => props.type,
    (newType) => {
        if (newType) {
            isVisible.value = true;

            if (props.autoHide) {
                const delay = props.autoHideDelay ?? (newType === 'complete' ? 3000 : 1500);
                setTimeout(() => {
                    isVisible.value = false;
                    emit('hidden');
                }, delay);
            }
        } else {
            isVisible.value = false;
        }
    },
    { immediate: true }
);
</script>

<template>
    <Transition name="feedback">
        <div v-if="isVisible && type" class="feedback-overlay" :class="type">
            <div class="feedback-content">
                <div class="feedback-icon">
                    <Check v-if="type === 'correct'" :size="48" />
                    <X v-else-if="type === 'incorrect'" :size="48" />
                    <Star v-else-if="type === 'complete'" :size="48" />
                </div>
                <p class="feedback-text">{{ displayMessage }}</p>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.feedback-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 100;
    pointer-events: none;
}

.feedback-overlay.correct {
    background-color: rgba(34, 197, 94, 0.1);
}

.feedback-overlay.incorrect {
    background-color: rgba(239, 68, 68, 0.1);
}

.feedback-overlay.complete {
    background-color: rgba(234, 179, 8, 0.15);
}

.feedback-content {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1rem;
    padding: 2rem 3rem;
    border-radius: 1rem;
    backdrop-filter: blur(8px);
}

.correct .feedback-content {
    background-color: rgba(34, 197, 94, 0.95);
    box-shadow: 0 0 60px rgba(34, 197, 94, 0.5);
}

.incorrect .feedback-content {
    background-color: rgba(239, 68, 68, 0.95);
    box-shadow: 0 0 60px rgba(239, 68, 68, 0.5);
}

.complete .feedback-content {
    background-color: rgba(234, 179, 8, 0.95);
    box-shadow: 0 0 60px rgba(234, 179, 8, 0.5);
}

.feedback-icon {
    color: white;
}

.feedback-text {
    font-size: 1.5rem;
    font-weight: 700;
    color: white;
    margin: 0;
    text-align: center;
}

/* Transition animations */
.feedback-enter-active {
    animation: feedbackIn 0.3s ease-out;
}

.feedback-leave-active {
    animation: feedbackOut 0.3s ease-in;
}

@keyframes feedbackIn {
    from {
        opacity: 0;
        transform: scale(0.8);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes feedbackOut {
    from {
        opacity: 1;
        transform: scale(1);
    }
    to {
        opacity: 0;
        transform: scale(0.8);
    }
}
</style>
