import { ref } from 'vue';

import type { Coordinate, GameConfig, GameState, Stone } from '@/types/go';

export interface UseGoAIOptions {
    aiColor?: Stone;
    enabled?: boolean;
}

export function useGoAI(options: UseGoAIOptions = {}) {
    const aiColor = ref<Stone>(options.aiColor ?? 'white');
    const enabled = ref(options.enabled ?? true);
    const isThinking = ref(false);
    const lastError = ref<string | null>(null);

    /**
     * Calculate AI move by calling the KataGo API
     * Returns null if AI should pass or on error
     */
    async function calculateMove(state: GameState, config: GameConfig): Promise<Coordinate | null> {
        // Clear previous error
        lastError.value = null;

        if (!enabled.value) {
            console.log('[KataGo] AI is disabled');
            return null;
        }
        if (state.isGameOver) {
            console.log('[KataGo] Game is over');
            return null;
        }
        if (state.currentPlayer !== aiColor.value) {
            console.log('[KataGo] Not AI turn');
            return null;
        }

        console.log(`[KataGo] Calculating move for ${aiColor.value}...`);
        const startTime = performance.now();
        isThinking.value = true;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const requestBody = {
                board: state.board,
                currentPlayer: state.currentPlayer,
                boardSize: config.boardSize,
                moveHistory: state.moveHistory,
                koPoint: state.koPoint,
            };
            console.log('[KataGo] Request:', { boardSize: config.boardSize, currentPlayer: state.currentPlayer });

            const response = await fetch('/api/go/ai-move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify(requestBody),
            });

            if (!response.ok) {
                const errorText = response.status === 429
                    ? 'Too many requests. Please wait a moment.'
                    : `AI service error (${response.status})`;
                throw new Error(errorText);
            }

            const data = await response.json();
            const elapsed = (performance.now() - startTime).toFixed(0);

            // Log engine info
            if (data.engine === 'katago') {
                console.log('[KataGo] Engine: KataGo');
            } else {
                console.warn('[KataGo] Engine: FALLBACK (KataGo unavailable)');
            }

            if (data.move) {
                console.log(`[KataGo] Move: (${data.move.x}, ${data.move.y}) in ${elapsed}ms`);
            } else {
                console.log(`[KataGo] Pass in ${elapsed}ms`);
            }

            return data.move;
        } catch (error) {
            const errorMessage = error instanceof Error ? error.message : 'AI move calculation failed';
            console.error('[KataGo] API error:', errorMessage);
            lastError.value = errorMessage;
            return null;
        } finally {
            isThinking.value = false;
        }
    }

    /**
     * Clear the last error
     */
    function clearError(): void {
        lastError.value = null;
    }

    /**
     * Set which color the AI plays as
     */
    function setAIColor(color: Stone): void {
        aiColor.value = color;
    }

    /**
     * Enable or disable AI
     */
    function setEnabled(value: boolean): void {
        enabled.value = value;
    }

    return {
        // State
        aiColor,
        enabled,
        isThinking,
        lastError,

        // Methods
        calculateMove,
        setAIColor,
        setEnabled,
        clearError,
    };
}
