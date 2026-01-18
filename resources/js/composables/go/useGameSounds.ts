import { ref } from 'vue';

import { useGameSettings } from './useGameSettings';

// Audio context singleton
let audioContext: AudioContext | null = null;

// Preloaded audio buffers
const audioBuffers = ref<Map<string, AudioBuffer>>(new Map());
const isLoaded = ref(false);

// Sound file paths
const SOUNDS = {
    stonePlace: '/sounds/stone-place.mp3',
    stoneCapture: '/sounds/stone-capture.mp3',
} as const;

type SoundName = keyof typeof SOUNDS;

// Get or create audio context
function getAudioContext(): AudioContext | null {
    if (typeof window === 'undefined') return null;

    if (!audioContext) {
        try {
            audioContext = new (window.AudioContext ||
                (window as unknown as { webkitAudioContext: typeof AudioContext }).webkitAudioContext)();
        } catch (e) {
            console.warn('Web Audio API not supported:', e);
            return null;
        }
    }

    return audioContext;
}

// Load a single audio file
async function loadAudioFile(url: string): Promise<AudioBuffer | null> {
    const ctx = getAudioContext();
    if (!ctx) return null;

    try {
        const response = await fetch(url);
        if (!response.ok) {
            console.warn(`Failed to fetch audio file: ${url}`);
            return null;
        }
        const arrayBuffer = await response.arrayBuffer();
        return await ctx.decodeAudioData(arrayBuffer);
    } catch (e) {
        console.warn(`Failed to load audio file ${url}:`, e);
        return null;
    }
}

// Preload all sounds
async function preloadSounds(): Promise<void> {
    if (isLoaded.value) return;

    const loadPromises = Object.entries(SOUNDS).map(async ([name, url]) => {
        const buffer = await loadAudioFile(url);
        if (buffer) {
            audioBuffers.value.set(name, buffer);
        }
    });

    await Promise.all(loadPromises);
    isLoaded.value = true;
}

// Play a sound
function playSound(name: SoundName, volume = 1.0): void {
    const ctx = getAudioContext();
    if (!ctx) return;

    // Resume context if suspended (required for autoplay policies)
    if (ctx.state === 'suspended') {
        ctx.resume();
    }

    const buffer = audioBuffers.value.get(name);
    if (!buffer) {
        console.warn(`Sound not loaded: ${name}`);
        return;
    }

    try {
        const source = ctx.createBufferSource();
        source.buffer = buffer;

        const gainNode = ctx.createGain();
        gainNode.gain.value = volume;

        source.connect(gainNode);
        gainNode.connect(ctx.destination);

        source.start(0);
    } catch (e) {
        console.warn(`Failed to play sound ${name}:`, e);
    }
}

export function useGameSounds() {
    const { settings } = useGameSettings();

    // Initialize sounds on first use
    preloadSounds();

    function playStonePlace(): void {
        if (settings.value.soundEffects) {
            playSound('stonePlace', 0.6);
        }
    }

    function playStoneCapture(): void {
        if (settings.value.soundEffects) {
            playSound('stoneCapture', 0.7);
        }
    }

    // Unlock audio context on user interaction (for mobile browsers)
    function unlockAudio(): void {
        const ctx = getAudioContext();
        if (ctx && ctx.state === 'suspended') {
            ctx.resume();
        }
    }

    return {
        isLoaded,
        playStonePlace,
        playStoneCapture,
        unlockAudio,
        preloadSounds,
    };
}
