import { ref, watch } from 'vue';

export type BoardTheme = 'classic' | 'kaya' | 'slate';

export interface GameSettings {
    showCoordinates: boolean;
    soundEffects: boolean;
    boardTheme: BoardTheme;
}

const STORAGE_KEY = 'go-game-settings';

const defaultSettings: GameSettings = {
    showCoordinates: true,
    soundEffects: true,
    boardTheme: 'classic',
};

// Load settings from localStorage
function loadSettings(): GameSettings {
    if (typeof window === 'undefined') {
        return { ...defaultSettings };
    }

    try {
        const stored = localStorage.getItem(STORAGE_KEY);
        if (stored) {
            const parsed = JSON.parse(stored);
            return {
                showCoordinates:
                    typeof parsed.showCoordinates === 'boolean'
                        ? parsed.showCoordinates
                        : defaultSettings.showCoordinates,
                soundEffects:
                    typeof parsed.soundEffects === 'boolean'
                        ? parsed.soundEffects
                        : defaultSettings.soundEffects,
                boardTheme:
                    ['classic', 'kaya', 'slate'].includes(parsed.boardTheme)
                        ? parsed.boardTheme
                        : defaultSettings.boardTheme,
            };
        }
    } catch (e) {
        console.warn('Failed to load game settings from localStorage:', e);
    }

    return { ...defaultSettings };
}

// Save settings to localStorage
function saveSettings(settings: GameSettings): void {
    if (typeof window === 'undefined') return;

    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
    } catch (e) {
        console.warn('Failed to save game settings to localStorage:', e);
    }
}

// Singleton state (shared across all components using this composable)
const settings = ref<GameSettings>(loadSettings());

export function useGameSettings() {
    // Watch for changes and persist
    watch(
        settings,
        (newSettings) => {
            saveSettings(newSettings);
        },
        { deep: true }
    );

    function setShowCoordinates(value: boolean) {
        settings.value.showCoordinates = value;
    }

    function setSoundEffects(value: boolean) {
        settings.value.soundEffects = value;
    }

    function setBoardTheme(theme: BoardTheme) {
        settings.value.boardTheme = theme;
    }

    function resetToDefaults() {
        settings.value = { ...defaultSettings };
    }

    return {
        settings,
        setShowCoordinates,
        setSoundEffects,
        setBoardTheme,
        resetToDefaults,
    };
}
