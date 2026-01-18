import { ref } from 'vue';

import type { MoveTree, AnalysisStudyFormData } from '@/types/analysis';

interface StudyResponse {
    id: number;
    title: string;
    updated_at: string;
}

interface ApiResponse {
    success: boolean;
    study?: StudyResponse;
    error?: string;
}

/**
 * Composable for managing analysis study API operations
 */
export function useAnalysisStudy() {
    const isSaving = ref(false);
    const lastSaved = ref<Date | null>(null);
    const error = ref<string | null>(null);

    /**
     * Get CSRF token from the page meta tag
     */
    function getCsrfToken(): string {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        return token ?? '';
    }

    /**
     * Create a new study
     */
    async function createStudy(data: AnalysisStudyFormData): Promise<StudyResponse | null> {
        isSaving.value = true;
        error.value = null;

        try {
            const response = await fetch('/api/go/studies', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            const result: ApiResponse = await response.json();

            if (!response.ok || !result.success) {
                error.value = result.error ?? 'Failed to create study';
                return null;
            }

            lastSaved.value = new Date();
            return result.study ?? null;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create study';
            return null;
        } finally {
            isSaving.value = false;
        }
    }

    /**
     * Update an existing study
     */
    async function updateStudy(
        studyId: number,
        data: Partial<AnalysisStudyFormData>
    ): Promise<StudyResponse | null> {
        isSaving.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/go/studies/${studyId}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify(data),
            });

            const result: ApiResponse = await response.json();

            if (!response.ok || !result.success) {
                error.value = result.error ?? 'Failed to save study';
                return null;
            }

            lastSaved.value = new Date();
            return result.study ?? null;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to save study';
            return null;
        } finally {
            isSaving.value = false;
        }
    }

    /**
     * Delete a study
     */
    async function deleteStudy(studyId: number): Promise<boolean> {
        isSaving.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/go/studies/${studyId}`, {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            const result: ApiResponse = await response.json();

            if (!response.ok || !result.success) {
                error.value = result.error ?? 'Failed to delete study';
                return false;
            }

            return true;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to delete study';
            return false;
        } finally {
            isSaving.value = false;
        }
    }

    /**
     * Create a study from an existing game
     */
    async function createFromGame(gameId: number): Promise<StudyResponse | null> {
        isSaving.value = true;
        error.value = null;

        try {
            const response = await fetch(`/api/go/studies/from-game/${gameId}`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                },
                credentials: 'same-origin',
            });

            const result: ApiResponse = await response.json();

            if (!response.ok || !result.success) {
                error.value = result.error ?? 'Failed to create study from game';
                return null;
            }

            return result.study ?? null;
        } catch (e) {
            error.value = e instanceof Error ? e.message : 'Failed to create study from game';
            return null;
        } finally {
            isSaving.value = false;
        }
    }

    /**
     * Auto-save with debounce (to be used with watch)
     */
    let saveTimeout: ReturnType<typeof setTimeout> | null = null;

    function debouncedSave(
        studyId: number,
        data: Partial<AnalysisStudyFormData>,
        delay: number = 2000
    ): void {
        if (saveTimeout) {
            clearTimeout(saveTimeout);
        }

        saveTimeout = setTimeout(async () => {
            try {
                await updateStudy(studyId, data);
            } catch (e) {
                console.error('[AnalysisStudy] Auto-save failed:', e);
                error.value = e instanceof Error ? e.message : 'Auto-save failed';
            }
        }, delay);
    }

    function cancelPendingSave(): void {
        if (saveTimeout) {
            clearTimeout(saveTimeout);
            saveTimeout = null;
        }
    }

    return {
        // State
        isSaving,
        lastSaved,
        error,

        // Actions
        createStudy,
        updateStudy,
        deleteStudy,
        createFromGame,
        debouncedSave,
        cancelPendingSave,
    };
}
