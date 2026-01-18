import { computed, onUnmounted, ref } from 'vue';

import type {
    MatchFoundPayload,
    MatchmakingStatus,
    QueueEntry,
    TimeControl,
} from '@/types/multiplayer';

export interface UseMatchmakingOptions {
    onMatchFound?: (payload: MatchFoundPayload) => void;
}

export function useMatchmaking(options: UseMatchmakingOptions = {}) {
    const isInQueue = ref(false);
    const isSearching = ref(false);
    const queueEntry = ref<QueueEntry | null>(null);
    const waitTimeSeconds = ref(0);
    const error = ref<string | null>(null);
    const matchFound = ref<MatchFoundPayload | null>(null);

    let waitTimeInterval: ReturnType<typeof setInterval> | null = null;
    let echoChannel: unknown = null;

    const waitTimeFormatted = computed(() => {
        const minutes = Math.floor(waitTimeSeconds.value / 60);
        const seconds = waitTimeSeconds.value % 60;
        return `${minutes}:${seconds.toString().padStart(2, '0')}`;
    });

    async function joinQueue(
        boardSize: 9 | 13 | 19,
        timeControl: TimeControl,
        isRanked: boolean = true,
        maxRatingDiff: number = 200
    ): Promise<boolean> {
        error.value = null;
        isSearching.value = true;

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/api/matchmaking/join', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
                body: JSON.stringify({
                    board_size: boardSize,
                    time_control_slug: timeControl.slug,
                    is_ranked: isRanked,
                    max_rating_diff: maxRatingDiff,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || data.message || 'Failed to join queue');
            }

            if (data.match_found) {
                matchFound.value = data.match_found;
                cleanup();
                options.onMatchFound?.(data.match_found);
                return true;
            }

            queueEntry.value = data.queue_entry;
            isInQueue.value = true;
            waitTimeSeconds.value = 0;

            // Start wait time counter
            startWaitTimeCounter();

            // Subscribe to matchmaking channel
            subscribeToMatchmaking();

            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to join queue';
            return false;
        } finally {
            isSearching.value = false;
        }
    }

    async function leaveQueue(): Promise<boolean> {
        error.value = null;

        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/api/matchmaking/leave', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                if (response.status === 404) {
                    cleanup();
                    return true;
                }
                const data = await response.json();
                throw new Error(data.error || data.message || 'Failed to leave queue');
            }

            cleanup();
            return true;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to leave queue';
            // Always cleanup local state so user can retry
            cleanup();
            return false;
        }
    }

    async function getStatus(): Promise<MatchmakingStatus | null> {
        try {
            const csrfToken =
                document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            const response = await fetch('/api/matchmaking/status', {
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                credentials: 'same-origin',
            });

            const data = await response.json();

            if (data.in_queue) {
                isInQueue.value = true;
                waitTimeSeconds.value = data.wait_time_seconds || 0;
                queueEntry.value = {
                    board_size: data.board_size,
                    time_control: data.time_control,
                    rating: data.rating,
                    max_rating_diff: data.max_rating_diff,
                    is_ranked: data.is_ranked,
                    joined_at: data.joined_at,
                    expires_at: data.expires_at,
                };
                startWaitTimeCounter();
                subscribeToMatchmaking();
            } else {
                cleanup();
            }

            return data;
        } catch (err) {
            error.value = err instanceof Error ? err.message : 'Failed to get status';
            return null;
        }
    }

    function startWaitTimeCounter(): void {
        stopWaitTimeCounter();
        waitTimeInterval = setInterval(() => {
            waitTimeSeconds.value++;
        }, 1000);
    }

    function stopWaitTimeCounter(): void {
        if (waitTimeInterval) {
            clearInterval(waitTimeInterval);
            waitTimeInterval = null;
        }
    }

    function subscribeToMatchmaking(): void {
        // Only subscribe if Echo is available
        if (typeof window !== 'undefined' && (window as unknown as { Echo?: unknown }).Echo) {
            const Echo = (window as unknown as { Echo: { private: (channel: string) => { listen: (event: string, callback: (payload: MatchFoundPayload) => void) => unknown }; leave: (channel: string) => void } }).Echo;
            const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');

            if (userId) {
                echoChannel = Echo.private(`matchmaking.${userId}`).listen(
                    '.match.found',
                    (payload: MatchFoundPayload) => {
                        matchFound.value = payload;
                        cleanup();
                        options.onMatchFound?.(payload);
                    }
                );
            }
        }
    }

    function unsubscribeFromMatchmaking(): void {
        if (
            echoChannel &&
            typeof window !== 'undefined' &&
            (window as unknown as { Echo?: unknown }).Echo
        ) {
            const Echo = (window as unknown as { Echo: { leave: (channel: string) => void } }).Echo;
            const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
            if (userId) {
                Echo.leave(`matchmaking.${userId}`);
            }
            echoChannel = null;
        }
    }

    function cleanup(): void {
        isInQueue.value = false;
        queueEntry.value = null;
        waitTimeSeconds.value = 0;
        stopWaitTimeCounter();
        unsubscribeFromMatchmaking();
    }

    function clearError(): void {
        error.value = null;
    }

    function clearMatchFound(): void {
        matchFound.value = null;
    }

    // Cleanup on component unmount
    onUnmounted(() => {
        cleanup();
    });

    return {
        // State
        isInQueue,
        isSearching,
        queueEntry,
        waitTimeSeconds,
        waitTimeFormatted,
        error,
        matchFound,

        // Methods
        joinQueue,
        leaveQueue,
        getStatus,
        clearError,
        clearMatchFound,
        cleanup,
    };
}
