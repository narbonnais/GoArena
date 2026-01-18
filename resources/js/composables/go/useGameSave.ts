import { ref } from 'vue';

import type { GameScore, GameState, Stone } from '@/types/go';

// Development-only logger to avoid exposing info in production
const isDev = import.meta.env.DEV;
const devLog = {
    log: (...args: unknown[]) => isDev && console.log(...args),
    error: (...args: unknown[]) => isDev && console.error(...args),
};

export interface GameSaveData {
    boardSize: 9 | 13 | 19;
    komi: number;
    winner: Stone | 'draw';
    endReason: 'score' | 'resignation';
    scoreMargin: number | null;
    score: GameScore | null;
    state: GameState;
    durationSeconds: number;
}

export interface InProgressGameData {
    boardSize: 9 | 13 | 19;
    komi: number;
    state: GameState;
    durationSeconds: number;
}

export interface GameUpdateData {
    winner?: Stone | 'draw' | null;
    endReason?: 'score' | 'resignation' | null;
    scoreMargin?: number | null;
    score?: GameScore | null;
    state: GameState;
    durationSeconds: number;
    isFinished: boolean;
}

function getXsrfToken(): string {
    const xsrfToken = document.cookie
        .split('; ')
        .find(row => row.startsWith('XSRF-TOKEN='))
        ?.split('=')[1];
    return xsrfToken ? decodeURIComponent(xsrfToken) : '';
}

/**
 * Fetch with timeout support
 */
async function fetchWithTimeout(
    url: string,
    options: RequestInit,
    timeoutMs: number = 30000
): Promise<Response> {
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), timeoutMs);

    try {
        const response = await fetch(url, {
            ...options,
            signal: controller.signal,
        });
        return response;
    } finally {
        clearTimeout(timeoutId);
    }
}

/**
 * Retry configuration
 */
interface RetryConfig {
    maxRetries: number;
    baseDelayMs: number;
    maxDelayMs: number;
}

const DEFAULT_RETRY_CONFIG: RetryConfig = {
    maxRetries: 3,
    baseDelayMs: 1000,
    maxDelayMs: 8000,
};

/**
 * Check if an error is retryable (network errors, 5xx server errors)
 */
function isRetryableError(error: unknown, response?: Response): boolean {
    // Network errors (fetch failed)
    if (error instanceof TypeError && error.message.includes('fetch')) {
        return true;
    }

    // Timeout errors
    if (error instanceof Error && error.name === 'AbortError') {
        return true;
    }

    // Server errors (5xx) are retryable
    if (response && response.status >= 500 && response.status < 600) {
        return true;
    }

    return false;
}

/**
 * Calculate delay with exponential backoff and jitter
 */
function calculateRetryDelay(attempt: number, config: RetryConfig): number {
    const exponentialDelay = config.baseDelayMs * Math.pow(2, attempt);
    const jitter = Math.random() * 0.3 * exponentialDelay; // 0-30% jitter
    return Math.min(exponentialDelay + jitter, config.maxDelayMs);
}

/**
 * Sleep for a given duration
 */
function sleep(ms: number): Promise<void> {
    return new Promise(resolve => setTimeout(resolve, ms));
}

/**
 * Fetch with timeout and retry support for transient failures
 */
async function fetchWithRetry(
    url: string,
    options: RequestInit,
    timeoutMs: number = 30000,
    retryConfig: RetryConfig = DEFAULT_RETRY_CONFIG
): Promise<Response> {
    let lastError: unknown;
    let lastResponse: Response | undefined;

    for (let attempt = 0; attempt <= retryConfig.maxRetries; attempt++) {
        try {
            const response = await fetchWithTimeout(url, options, timeoutMs);

            // Don't retry client errors (4xx) except for 408 (timeout) and 429 (rate limit)
            if (response.ok || (response.status >= 400 && response.status < 500 && response.status !== 408 && response.status !== 429)) {
                return response;
            }

            lastResponse = response;

            // Check if we should retry server errors
            if (!isRetryableError(null, response) || attempt === retryConfig.maxRetries) {
                return response;
            }

            devLog.log(`[GameSave] Retrying request (attempt ${attempt + 1}/${retryConfig.maxRetries}) after ${response.status} error`);
        } catch (error) {
            lastError = error;

            if (!isRetryableError(error) || attempt === retryConfig.maxRetries) {
                throw error;
            }

            devLog.log(`[GameSave] Retrying request (attempt ${attempt + 1}/${retryConfig.maxRetries}) after error:`, error);
        }

        // Wait before retrying
        const delay = calculateRetryDelay(attempt, retryConfig);
        await sleep(delay);
    }

    // Should not reach here, but just in case
    if (lastResponse) {
        return lastResponse;
    }
    throw lastError;
}

export function useGameSave() {
    const isSaving = ref(false);
    const saveError = ref<string | null>(null);
    const savedGameId = ref<number | null>(null);

    async function saveGame(data: GameSaveData): Promise<boolean> {
        devLog.log('[GameSave] Attempting to save finished game:', data);
        isSaving.value = true;
        saveError.value = null;

        try {
            const decodedToken = getXsrfToken();
            devLog.log('[GameSave] XSRF token found:', !!decodedToken);

            const response = await fetchWithRetry('/go/games', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': decodedToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    board_size: data.boardSize,
                    komi: data.komi,
                    winner: data.winner,
                    end_reason: data.endReason,
                    score_margin: data.scoreMargin,
                    move_count: data.state.moveHistory.length,
                    black_score: data.score?.blackTotal ?? 0,
                    white_score: data.score?.whiteTotal ?? 0,
                    black_captures: data.state.blackCaptures,
                    white_captures: data.state.whiteCaptures,
                    move_history: data.state.moveHistory,
                    duration_seconds: data.durationSeconds,
                    is_finished: true,
                }),
            });

            devLog.log('[GameSave] Response status:', response.status);
            if (!response.ok) {
                if (response.status === 401) {
                    devLog.log('[GameSave] User not authenticated, skipping save');
                    return false;
                }
                const errorText = await response.text();
                devLog.error('[GameSave] Error response:', errorText);
                throw new Error(`Failed to save game: ${response.status}`);
            }

            const result = await response.json();
            devLog.log('[GameSave] Game saved successfully:', result);
            savedGameId.value = result.game_id;
            return true;
        } catch (error) {
            devLog.error('[GameSave] Error saving game:', error);
            if (error instanceof Error && error.name === 'AbortError') {
                saveError.value = 'Request timed out. Please check your connection and try again.';
            } else {
                saveError.value = error instanceof Error ? error.message : 'Unknown error';
            }
            return false;
        } finally {
            isSaving.value = false;
        }
    }

    async function saveInProgressGame(data: InProgressGameData): Promise<number | null> {
        devLog.log('[GameSave] Attempting to save in-progress game:', data);
        isSaving.value = true;
        saveError.value = null;

        try {
            const decodedToken = getXsrfToken();

            const response = await fetchWithRetry('/go/games', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': decodedToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    board_size: data.boardSize,
                    komi: data.komi,
                    winner: null,
                    end_reason: null,
                    score_margin: null,
                    move_count: data.state.moveHistory.length,
                    black_score: 0,
                    white_score: 0,
                    black_captures: data.state.blackCaptures,
                    white_captures: data.state.whiteCaptures,
                    move_history: data.state.moveHistory,
                    duration_seconds: data.durationSeconds,
                    is_finished: false,
                }),
            });

            if (!response.ok) {
                if (response.status === 401) {
                    devLog.log('[GameSave] User not authenticated, skipping save');
                    return null;
                }
                const errorText = await response.text();
                devLog.error('[GameSave] Error response:', errorText);
                throw new Error(`Failed to save game: ${response.status}`);
            }

            const result = await response.json();
            devLog.log('[GameSave] In-progress game saved successfully:', result);
            savedGameId.value = result.game_id;
            return result.game_id;
        } catch (error) {
            devLog.error('[GameSave] Error saving in-progress game:', error);
            if (error instanceof Error && error.name === 'AbortError') {
                saveError.value = 'Request timed out. Please check your connection and try again.';
            } else {
                saveError.value = error instanceof Error ? error.message : 'Unknown error';
            }
            return null;
        } finally {
            isSaving.value = false;
        }
    }

    async function updateGame(gameId: number, data: GameUpdateData): Promise<boolean> {
        devLog.log('[GameSave] Attempting to update game:', gameId, data);
        isSaving.value = true;
        saveError.value = null;

        try {
            const decodedToken = getXsrfToken();

            const response = await fetchWithRetry(`/go/games/${gameId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-XSRF-TOKEN': decodedToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    winner: data.winner,
                    end_reason: data.endReason,
                    score_margin: data.scoreMargin,
                    move_count: data.state.moveHistory.length,
                    black_score: data.score?.blackTotal ?? 0,
                    white_score: data.score?.whiteTotal ?? 0,
                    black_captures: data.state.blackCaptures,
                    white_captures: data.state.whiteCaptures,
                    move_history: data.state.moveHistory,
                    duration_seconds: data.durationSeconds,
                    is_finished: data.isFinished,
                }),
            });

            if (!response.ok) {
                if (response.status === 401) {
                    devLog.log('[GameSave] User not authenticated');
                    return false;
                }
                const errorText = await response.text();
                devLog.error('[GameSave] Error response:', errorText);
                throw new Error(`Failed to update game: ${response.status}`);
            }

            const result = await response.json();
            devLog.log('[GameSave] Game updated successfully:', result);
            return true;
        } catch (error) {
            devLog.error('[GameSave] Error updating game:', error);
            if (error instanceof Error && error.name === 'AbortError') {
                saveError.value = 'Request timed out. Please check your connection and try again.';
            } else {
                saveError.value = error instanceof Error ? error.message : 'Unknown error';
            }
            return false;
        } finally {
            isSaving.value = false;
        }
    }

    function reset() {
        isSaving.value = false;
        saveError.value = null;
        savedGameId.value = null;
    }

    return {
        isSaving,
        saveError,
        savedGameId,
        saveGame,
        saveInProgressGame,
        updateGame,
        reset,
    };
}
