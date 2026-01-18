import { ref, computed } from 'vue';

export interface GoBot {
    id: string;
    name: string;
    avatarUrl: string;
    personality: string;
    color: string;
}

// Using Unsplash images - symbolic/artistic nature images in the spirit of Go/Paduk
// Free to use under Unsplash license (https://unsplash.com/license)
// All bots now use the same full-strength AI engine
export const GO_BOTS: GoBot[] = [
    {
        id: 'hana',
        name: 'Hana',
        avatarUrl: 'https://images.unsplash.com/photo-1522383225653-ed111181a951?w=100&h=100&fit=crop',
        personality: 'Loves patterns',
        color: '#fce7f3',
    },
    {
        id: 'kenji',
        name: 'Kenji',
        avatarUrl: 'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=100&h=100&fit=crop',
        personality: 'Solid and steady',
        color: '#d1d5db',
    },
    {
        id: 'mei',
        name: 'Mei',
        avatarUrl: 'https://images.unsplash.com/photo-1474557157379-8aa74a6ef541?w=100&h=100&fit=crop',
        personality: 'Aggressive player',
        color: '#fecaca',
    },
    {
        id: 'takeshi',
        name: 'Takeshi',
        avatarUrl: 'https://images.unsplash.com/photo-1577493340887-b7bfff550145?w=100&h=100&fit=crop',
        personality: 'Strong positional',
        color: '#bfdbfe',
    },
    {
        id: 'akira',
        name: 'Akira',
        avatarUrl: 'https://images.unsplash.com/photo-1553736277-055142d018f0?w=100&h=100&fit=crop',
        personality: 'Near-dan precision',
        color: '#c7d2fe',
    },
    {
        id: 'yuki',
        name: 'Master Yuki',
        avatarUrl: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=100&h=100&fit=crop',
        personality: 'Expert reader',
        color: '#fbcfe8',
    },
];

const STORAGE_KEY_BOT = 'go-selected-bot';
const STORAGE_KEY_BOARD_SIZE = 'go-board-size';

const DEFAULT_BOT_ID = 'hana';

export function getBotById(id: string): GoBot | undefined {
    return GO_BOTS.find(bot => bot.id === id);
}

export function getDefaultBot(): GoBot {
    return GO_BOTS.find(bot => bot.id === DEFAULT_BOT_ID) ?? GO_BOTS[0];
}

export function useGoBots() {
    const loadStoredBotId = (): string => {
        if (typeof window === 'undefined') return DEFAULT_BOT_ID;
        return localStorage.getItem(STORAGE_KEY_BOT) ?? DEFAULT_BOT_ID;
    };

    const loadStoredBoardSize = (): 9 | 13 | 19 => {
        if (typeof window === 'undefined') return 9;
        const stored = localStorage.getItem(STORAGE_KEY_BOARD_SIZE);
        if (stored === '13') return 13;
        if (stored === '19') return 19;
        return 9;
    };

    const selectedBotId = ref<string>(loadStoredBotId());
    const boardSize = ref<9 | 13 | 19>(loadStoredBoardSize());

    const selectedBot = computed<GoBot>(() => {
        return getBotById(selectedBotId.value) ?? getDefaultBot();
    });

    const selectBot = (botId: string): void => {
        const bot = getBotById(botId);
        if (bot) {
            selectedBotId.value = botId;
            if (typeof window !== 'undefined') {
                localStorage.setItem(STORAGE_KEY_BOT, botId);
            }
        }
    };

    const setBoardSize = (size: 9 | 13 | 19): void => {
        boardSize.value = size;
        if (typeof window !== 'undefined') {
            localStorage.setItem(STORAGE_KEY_BOARD_SIZE, String(size));
        }
    };

    return {
        bots: GO_BOTS,
        selectedBot,
        selectedBotId,
        selectBot,
        boardSize,
        setBoardSize,
    };
}
