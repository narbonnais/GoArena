import { ref, shallowRef } from 'vue';

import type { AnalysisResult, BoardState, Coordinate, Stone } from '@/types/go';

export interface AnalysisApiSettings {
    maxVisits: number;
    numSearchThreads: number;
    multiPV: number;
}

/**
 * Get CSRF token from meta tag
 */
function getCsrfToken(): string {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

export function useGoAnalysis() {
    const analysis = shallowRef<AnalysisResult | null>(null);
    const isAnalyzing = ref(false);
    const showSuggestedMove = ref(false);
    const suggestedMoves = ref<Coordinate[]>([]);
    const error = ref<string | null>(null);

    // Track request ID to ignore stale responses
    let currentRequestId = 0;
    let abortController: AbortController | null = null;

    // Default timeout for analysis requests (10 seconds)
    const ANALYSIS_TIMEOUT_MS = 10000;
    // Retry configuration
    const MAX_RETRIES = 2;
    const RETRY_DELAY_MS = 1000;

    async function analyzePosition(board: BoardState, currentPlayer: Stone, size: number, settings?: AnalysisApiSettings): Promise<AnalysisResult | null> {
        // Cancel any pending request
        if (abortController) {
            abortController.abort();
        }

        // Increment request ID to track this request
        const requestId = ++currentRequestId;
        abortController = new AbortController();

        // Note: Clearing of suggestions is now done by the caller (synchronously)
        // before invoking this async function, to ensure immediate UI update

        isAnalyzing.value = true;
        error.value = null;

        let lastError: Error | null = null;

        try {
            for (let attempt = 0; attempt <= MAX_RETRIES; attempt++) {
                // Check if request was superseded before attempting
                if (requestId !== currentRequestId) {
                    return null;
                }

                // Create a timeout that will abort the request
                const timeoutId = setTimeout(() => {
                    abortController?.abort();
                }, ANALYSIS_TIMEOUT_MS);

                try {
                    const response = await fetch('/api/go/analyze', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': getCsrfToken(),
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            board,
                            currentPlayer,
                            size,
                            settings,
                        }),
                        signal: abortController.signal,
                    });

                    // Clear timeout since request completed
                    clearTimeout(timeoutId);

                    // Ignore stale responses (check before parsing)
                    if (requestId !== currentRequestId) {
                        return null;
                    }

                    // Check response status before parsing JSON
                    if (!response.ok) {
                        // Retry on 5xx server errors, but not on 4xx client errors
                        if (response.status >= 500 && attempt < MAX_RETRIES) {
                            lastError = new Error(`Server error: ${response.status}`);
                            await new Promise(resolve => setTimeout(resolve, RETRY_DELAY_MS * (attempt + 1)));
                            continue;
                        }
                        error.value = `Server error: ${response.status}`;
                        return null;
                    }

                    const data = await response.json();

                    // Check again after JSON parsing in case another request started
                    if (requestId !== currentRequestId) {
                        return null;
                    }

                    // Handle error responses without storing them in analysis state
                    if (data.error) {
                        error.value = data.error;
                        return null;
                    }

                    analysis.value = data;

                    // Update suggested moves from top moves
                    if (data.topMoves && data.topMoves.length > 0) {
                        suggestedMoves.value = data.topMoves.map((m: { coordinate: Coordinate }) => m.coordinate);
                    } else {
                        suggestedMoves.value = [];
                    }

                    return data;
                } catch (e) {
                    clearTimeout(timeoutId);

                    // Handle abort errors (including timeout) - don't retry
                    if (e instanceof Error && e.name === 'AbortError') {
                        // Check if this was a timeout vs user cancellation
                        if (requestId === currentRequestId) {
                            error.value = 'Analysis timed out. Try again or reduce analysis depth.';
                        }
                        return null;
                    }

                    // Retry on network errors
                    lastError = e instanceof Error ? e : new Error('Analysis failed');
                    if (attempt < MAX_RETRIES) {
                        await new Promise(resolve => setTimeout(resolve, RETRY_DELAY_MS * (attempt + 1)));
                        continue;
                    }

                    error.value = lastError.message;
                    return null;
                }
            }

            // All retries exhausted
            error.value = lastError?.message ?? 'Analysis failed after retries';
            return null;
        } finally {
            // Only clear analyzing if this is the current request
            if (requestId === currentRequestId) {
                isAnalyzing.value = false;
            }
        }
    }

    async function getBestMove(board: BoardState, currentPlayer: Stone, size: number): Promise<Coordinate | null> {
        isAnalyzing.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/go/best-move', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    board,
                    currentPlayer,
                    size,
                }),
            });

            // Check response status before parsing JSON
            if (!response.ok) {
                error.value = `Server error: ${response.status}`;
                return null;
            }

            const data = await response.json();

            if (data.error) {
                error.value = data.error;
                return null;
            }

            if (data.move) {
                suggestedMoves.value = [data.move];
                showSuggestedMove.value = true;
            }

            return data.move;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to get best move';
            return null;
        } finally {
            isAnalyzing.value = false;
        }
    }

    function toggleSuggestedMove() {
        showSuggestedMove.value = !showSuggestedMove.value;
    }

    function hideSuggestedMove() {
        showSuggestedMove.value = false;
    }

    function clearAnalysis() {
        analysis.value = null;
        suggestedMoves.value = [];
        showSuggestedMove.value = false;
        error.value = null;
    }

    /**
     * Cancel any pending analysis request.
     * Should be called when component unmounts to prevent memory leaks.
     */
    function cancelPendingAnalysis() {
        if (abortController) {
            abortController.abort();
            abortController = null;
        }
        isAnalyzing.value = false;
    }

    return {
        // State
        analysis,
        isAnalyzing,
        showSuggestedMove,
        suggestedMoves,
        error,

        // Actions
        analyzePosition,
        getBestMove,
        toggleSuggestedMove,
        hideSuggestedMove,
        clearAnalysis,
        cancelPendingAnalysis,
    };
}
