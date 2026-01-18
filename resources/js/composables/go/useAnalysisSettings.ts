import { ref, computed, watch } from 'vue';

export interface AnalysisSettings {
    maxVisits: number;
    numSearchThreads: number;
    multiPV: number;
    autoAnalyze: boolean;
}

export interface AnalysisPreset {
    name: string;
    label: string;
    maxVisits: number;
}

const STORAGE_KEY = 'go-analysis-settings';

const LIMITS = {
    maxVisits: { min: 100, max: 5000, default: 500 },
};

// Backend defaults - not user configurable
const BACKEND_DEFAULTS = {
    numSearchThreads: 2,
    multiPV: 5,
};

const PRESETS: AnalysisPreset[] = [
    { name: 'fast', label: 'Fast', maxVisits: 200 },
    { name: 'balanced', label: 'Balanced', maxVisits: 500 },
    { name: 'deep', label: 'Deep', maxVisits: 2000 },
    { name: 'maximum', label: 'Maximum', maxVisits: 5000 },
];

function getDefaultSettings(): AnalysisSettings {
    return {
        maxVisits: LIMITS.maxVisits.default,
        numSearchThreads: BACKEND_DEFAULTS.numSearchThreads,
        multiPV: BACKEND_DEFAULTS.multiPV,
        autoAnalyze: true,
    };
}

function loadSettings(): AnalysisSettings {
    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            // Validate the parsed object has expected shape
            if (typeof parsed !== 'object' || parsed === null) {
                console.warn('Invalid analysis settings format, using defaults');
                return getDefaultSettings();
            }
            return {
                maxVisits: clamp(
                    typeof parsed.maxVisits === 'number' ? parsed.maxVisits : LIMITS.maxVisits.default,
                    LIMITS.maxVisits.min,
                    LIMITS.maxVisits.max
                ),
                numSearchThreads: BACKEND_DEFAULTS.numSearchThreads,
                multiPV: BACKEND_DEFAULTS.multiPV,
                autoAnalyze: typeof parsed.autoAnalyze === 'boolean' ? parsed.autoAnalyze : true,
            };
        }
    } catch (e) {
        console.warn('Failed to load analysis settings from localStorage:', e);
    }
    return getDefaultSettings();
}

function saveSettings(settings: AnalysisSettings): { success: boolean; error?: string } {
    try {
        // Only save user-configurable settings
        localStorage.setItem(STORAGE_KEY, JSON.stringify({
            maxVisits: settings.maxVisits,
            autoAnalyze: settings.autoAnalyze,
        }));
        return { success: true };
    } catch (e) {
        // Handle quota exceeded and other storage errors
        if (e instanceof DOMException && e.name === 'QuotaExceededError') {
            console.warn('localStorage quota exceeded for analysis settings');
            return { success: false, error: 'Storage quota exceeded. Settings may not persist.' };
        }
        console.warn('Failed to save analysis settings:', e);
        return { success: false, error: 'Failed to save settings.' };
    }
}

function clamp(value: number, min: number, max: number): number {
    return Math.max(min, Math.min(max, value));
}

export function useAnalysisSettings() {
    const settings = ref<AnalysisSettings>(loadSettings());
    const saveError = ref<string | null>(null);

    // Watch for changes and save to localStorage
    watch(settings, (newSettings) => {
        const result = saveSettings(newSettings);
        saveError.value = result.success ? null : (result.error ?? 'Failed to save settings');
    }, { deep: true });

    // Computed for current preset (if matching)
    const currentPreset = computed(() => {
        return PRESETS.find(p => p.maxVisits === settings.value.maxVisits)?.name ?? null;
    });

    // Apply a preset
    function applyPreset(presetName: string) {
        const preset = PRESETS.find(p => p.name === presetName);
        if (preset) {
            settings.value = {
                ...settings.value,
                maxVisits: preset.maxVisits,
            };
        }
    }

    // Individual setters with clamping
    function setMaxVisits(value: number) {
        settings.value = {
            ...settings.value,
            maxVisits: clamp(value, LIMITS.maxVisits.min, LIMITS.maxVisits.max),
        };
    }

    function setAutoAnalyze(value: boolean) {
        settings.value = {
            ...settings.value,
            autoAnalyze: value,
        };
    }

    // Reset to defaults
    function resetToDefaults() {
        settings.value = getDefaultSettings();
    }

    // Get settings for API call (without autoAnalyze which is frontend-only)
    const apiSettings = computed(() => ({
        maxVisits: settings.value.maxVisits,
        numSearchThreads: settings.value.numSearchThreads,
        multiPV: settings.value.multiPV,
    }));

    return {
        // State
        settings,
        currentPreset,
        presets: PRESETS,
        apiSettings,
        saveError,

        // Actions
        applyPreset,
        setMaxVisits,
        setAutoAnalyze,
        resetToDefaults,
    };
}
