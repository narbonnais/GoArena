<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Trophy, Frown, Settings } from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

import GameSettingsModal from '@/components/go/GameSettingsModal.vue';
import GameTimer from '@/components/go/GameTimer.vue';
import GoBoard from '@/components/go/GoBoard.vue';
import GoGameControls from '@/components/go/GoGameControls.vue';
import GoPlayerInfo from '@/components/go/GoPlayerInfo.vue';
import { useGameSave } from '@/composables/go/useGameSave';
import { useGoAI } from '@/composables/go/useGoAI';
import { getBotById, getDefaultBot, type GoBot } from '@/composables/go/useGoBots';
import { useGoGame } from '@/composables/go/useGoGame';
import type { Coordinate, ResumeGame } from '@/types/go';

// Props from Inertia
const props = defineProps<{
    boardSize?: 9 | 13 | 19;
    botId?: string;
    resumeGame?: ResumeGame;
    timeControl?: number; // in seconds
}>();

// Initialize game with props
const boardSize = props.resumeGame?.board_size ?? props.boardSize ?? 9;

// Get bot info
const bot = computed<GoBot>(() => {
    if (props.botId) {
        return getBotById(props.botId) ?? getDefaultBot();
    }
    return getDefaultBot();
});

const game = useGoGame({
    boardSize,
    resumeGame: props.resumeGame,
});
const ai = useGoAI({ aiColor: 'white' });
const gameSave = useGameSave();

// Track game ID (for resumed games)
const currentGameId = ref<number | null>(props.resumeGame?.id ?? null);

// Track game timing and save state
// If resuming, add the previous duration to the start time offset
const previousDuration = props.resumeGame?.duration_seconds ?? 0;
const gameStartTime = ref<number>(Date.now() - previousDuration * 1000);
const gameSaved = ref(false);

// Timer settings
const initialTimeControl = props.timeControl ?? 300; // default 5 minutes

// Settings modal
const showSettings = ref(false);
const showCoordinates = ref(false);
const soundEffects = ref(true);

// Watch for game over and auto-save
watch(
    () => game.isGameOver.value,
    async (isOver) => {
        console.log('[Play] Game over watch triggered, isOver:', isOver, 'gameSaved:', gameSaved.value);
        if (isOver && !gameSaved.value) {
            gameSaved.value = true;
            const durationSeconds = Math.floor((Date.now() - gameStartTime.value) / 1000);
            console.log('[Play] Saving game, duration:', durationSeconds);

            // Determine end reason and score margin
            const endReason = game.score.value ? 'score' : 'resignation';
            const scoreMargin = game.score.value?.margin ?? null;
            // Use score winner for games that ended by scoring, state winner for resignation
            const winner = game.score.value?.winner ?? game.winner.value ?? 'draw';

            if (currentGameId.value) {
                // Update existing game
                await gameSave.updateGame(currentGameId.value, {
                    winner,
                    endReason,
                    scoreMargin,
                    score: game.score.value,
                    state: game.state.value,
                    durationSeconds,
                    isFinished: true,
                });
            } else {
                // Create new finished game
                await gameSave.saveGame({
                    boardSize,
                    komi: game.config.value.komi,
                    winner,
                    endReason,
                    scoreMargin,
                    score: game.score.value,
                    state: game.state.value,
                    durationSeconds,
                });
            }
            console.log('[Play] Save result, Error:', gameSave.saveError.value);
        }
    }
);

// Game result message
const gameResultMessage = computed(() => {
    if (!game.isGameOver.value) return null;

    // Use score winner for games that ended by scoring, state winner for resignation
    const winner = game.score.value?.winner ?? game.winner.value;

    if (winner === 'black') {
        return game.score.value
            ? `Black wins by ${game.score.value.margin} points!`
            : 'Black wins by resignation!';
    } else if (winner === 'white') {
        return game.score.value
            ? `White wins by ${game.score.value.margin} points!`
            : 'White wins by resignation!';
    }
    return 'Game is a draw!';
});

// Determine if user won
const userWon = computed(() => {
    if (!game.isGameOver.value) return null;
    const winner = game.score.value?.winner ?? game.winner.value;
    return winner === 'black';
});

// Handle player move
async function handlePlay(coord: Coordinate) {
    if (ai.isThinking.value) return;
    if (game.currentPlayer.value !== 'black') return;

    const success = game.play(coord);
    if (success) {
        await triggerAIMove();
    }
}

// Handle pass
async function handlePass() {
    if (ai.isThinking.value) return;
    if (game.currentPlayer.value !== 'black') return;

    game.pass();
    await triggerAIMove();
}

// Handle resign
function handleResign() {
    game.resign();
}

// Handle undo
function handleUndo() {
    game.undo();
}

// Handle timeout
function handleTimeout(player: 'black' | 'white') {
    // When a player runs out of time, they lose
    if (player === 'black') {
        game.resign(); // Player loses
    }
    // AI timeout - player wins (but AI shouldn't timeout in practice)
}

// Handle exit - save in-progress game and return to home
async function handleExit() {
    // If game is over, just navigate away
    if (game.isGameOver.value) {
        router.visit('/go');
        return;
    }

    // Save in-progress game before exiting
    const durationSeconds = Math.floor((Date.now() - gameStartTime.value) / 1000);

    if (currentGameId.value) {
        // Update existing game
        await gameSave.updateGame(currentGameId.value, {
            winner: null,
            endReason: null,
            scoreMargin: null,
            score: null,
            state: game.state.value,
            durationSeconds,
            isFinished: false,
        });
    } else {
        // Create new in-progress game
        await gameSave.saveInProgressGame({
            boardSize,
            komi: game.config.value.komi,
            state: game.state.value,
            durationSeconds,
        });
    }

    router.visit('/go');
}

// Trigger AI move if it's AI's turn
async function triggerAIMove() {
    if (game.isGameOver.value) return;
    if (game.currentPlayer.value !== ai.aiColor.value) return;

    const move = await ai.calculateMove(game.state.value, game.config.value);

    if (move === null) {
        // AI passes
        game.pass();
    } else {
        game.play(move);
    }

    // Check if game ended or if AI should play again (shouldn't happen in normal play)
    if (!game.isGameOver.value && game.currentPlayer.value === ai.aiColor.value) {
        await triggerAIMove();
    }
}

// Board interaction disabled when AI is thinking or game is over
const isBoardDisabled = computed(() => {
    return ai.isThinking.value || game.isGameOver.value || game.currentPlayer.value !== 'black';
});

// On mount, if resuming and it's AI's turn, trigger AI move
onMounted(() => {
    if (props.resumeGame && game.currentPlayer.value === ai.aiColor.value && !game.isGameOver.value) {
        triggerAIMove();
    }
});
</script>

<template>
    <Head title="Play Go" />

    <div class="play-page">
        <!-- Compact Header -->
        <header class="play-header">
            <button class="exit-btn" @click="handleExit">
                <ArrowLeft :size="18" />
                <span>Exit</span>
            </button>
            <div class="game-info">
                <span class="board-size">{{ boardSize }}×{{ boardSize }}</span>
                <span class="komi-badge">Komi {{ game.config.value.komi }}</span>
                <span class="vs">vs</span>
                <img :src="bot.avatarUrl" :alt="bot.name" class="bot-avatar" />
                <span class="bot-name">{{ bot.name }}</span>
            </div>
            <button class="settings-btn" @click="showSettings = true">
                <Settings :size="18" />
            </button>
        </header>

        <!-- Main Game Area -->
        <main class="game-area">
            <!-- Left Player Panel (You - Black) -->
            <div class="player-panel left-panel">
                <GameTimer
                    :initial-time="initialTimeControl"
                    :is-running="game.currentPlayer.value === 'black' && !game.isGameOver.value"
                    @timeout="handleTimeout('black')"
                />
                <GoPlayerInfo
                    color="black"
                    name="You"
                    :captures="game.blackCaptures.value"
                    :is-current-player="game.currentPlayer.value === 'black' && !game.isGameOver.value"
                />
            </div>

            <!-- Board Container -->
            <div class="board-wrapper">
                <GoBoard
                    :board="game.board.value"
                    :size="boardSize"
                    :current-player="game.currentPlayer.value"
                    :last-move="game.lastMove.value"
                    :disabled="isBoardDisabled"
                    @play="handlePlay"
                />
            </div>

            <!-- Right Player Panel (AI - White) -->
            <div class="player-panel right-panel">
                <GameTimer
                    :initial-time="initialTimeControl"
                    :is-running="game.currentPlayer.value === 'white' && !game.isGameOver.value"
                    @timeout="handleTimeout('white')"
                />
                <GoPlayerInfo
                    color="white"
                    :name="bot.name"
                    :avatar-url="bot.avatarUrl"
                    :captures="game.whiteCaptures.value"
                    :is-current-player="game.currentPlayer.value === 'white' && !game.isGameOver.value"
                    :is-thinking="ai.isThinking.value"
                />
            </div>
        </main>

        <!-- Game Over Overlay -->
        <div v-if="game.isGameOver.value" class="game-over-overlay">
            <div class="game-over-modal">
                <div class="result-icon" :class="{ win: userWon, loss: !userWon }">
                    <Trophy v-if="userWon" :size="48" />
                    <Frown v-else :size="48" />
                </div>
                <h2 class="result-title">{{ userWon ? 'Victory!' : 'Defeat' }}</h2>
                <p class="result-message">{{ gameResultMessage }}</p>

                <div v-if="game.score.value" class="score-breakdown">
                    <div class="score-side black">
                        <div class="score-stone black-stone"></div>
                        <div class="score-info">
                            <div class="score-label">Black (You)</div>
                            <div class="score-value">{{ game.score.value.blackTotal }}</div>
                            <div class="score-detail">Territory: {{ game.score.value.blackTerritory }}</div>
                            <div class="score-detail">Captures: {{ game.score.value.blackCaptures }}</div>
                        </div>
                    </div>
                    <div class="score-divider">vs</div>
                    <div class="score-side white">
                        <div class="score-stone white-stone"></div>
                        <div class="score-info">
                            <div class="score-label">White ({{ bot.name }})</div>
                            <div class="score-value">{{ game.score.value.whiteTotal }}</div>
                            <div class="score-detail">Territory: {{ game.score.value.whiteTerritory }}</div>
                            <div class="score-detail">Captures: {{ game.score.value.whiteCaptures }}</div>
                            <div class="score-detail">Komi: {{ game.score.value.komi }}</div>
                        </div>
                    </div>
                </div>

                <div class="modal-actions">
                    <button class="btn btn-primary" @click="handleExit">Continue</button>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <footer class="controls-bar">
            <GoGameControls
                :disabled="ai.isThinking.value || game.currentPlayer.value !== 'black'"
                :is-game-over="game.isGameOver.value"
                :can-undo="game.canUndo.value"
                @pass="handlePass"
                @resign="handleResign"
                @undo="handleUndo"
            />
        </footer>

        <!-- Settings Modal -->
        <GameSettingsModal
            :open="showSettings"
            :show-coordinates="showCoordinates"
            :sound-effects="soundEffects"
            @close="showSettings = false"
            @update:show-coordinates="showCoordinates = $event"
            @update:sound-effects="soundEffects = $event"
        />
    </div>
</template>

<style scoped>
.play-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background-color: var(--background);
}

/* Header */
.play-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background-color: var(--background-deep);
    border-bottom: 1px solid var(--border);
}

.exit-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--muted-foreground);
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.exit-btn:hover {
    color: var(--foreground);
    border-color: var(--foreground);
    background-color: var(--accent);
}

.game-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
}

.board-size {
    font-weight: 700;
    color: var(--foreground);
    font-size: 1rem;
}

.komi-badge {
    padding: 0.25rem 0.5rem;
    background-color: var(--muted);
    color: var(--muted-foreground);
    border-radius: 0.25rem;
    font-size: 0.75rem;
    font-weight: 500;
}

.vs {
    color: var(--muted-foreground);
}

.bot-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    object-fit: cover;
}

.bot-name {
    font-weight: 600;
    color: var(--go-green);
}

.settings-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: transparent;
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    color: var(--muted-foreground);
    cursor: pointer;
    transition: all 0.15s ease;
}

.settings-btn:hover {
    color: var(--foreground);
    border-color: var(--foreground);
    background-color: var(--accent);
}

/* Main Game Area */
.game-area {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    gap: 1rem;
}

@media (min-width: 1024px) {
    .game-area {
        flex-direction: row;
        gap: 2rem;
        padding: 2rem;
    }
}

.player-panel {
    order: 2;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    align-items: center;
}

@media (min-width: 1024px) {
    .left-panel {
        order: 1;
    }

    .right-panel {
        order: 3;
    }

    .board-wrapper {
        order: 2;
    }
}

.board-wrapper {
    order: 1;
}

/* Game Over Overlay */
.game-over-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
    padding: 1rem;
}

.game-over-modal {
    background-color: var(--card);
    border-radius: 1rem;
    padding: 2rem;
    max-width: 400px;
    width: 100%;
    text-align: center;
    border: 1px solid var(--border);
}

.result-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    background-color: var(--muted);
    color: var(--muted-foreground);
}

.result-icon.win {
    background-color: var(--go-green-muted);
    color: var(--go-green);
}

.result-icon.loss {
    background-color: hsl(0 84% 60% / 0.15);
    color: hsl(0 84% 60%);
}

.result-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0 0 0.5rem;
}

.result-title.win {
    color: var(--go-green);
}

.result-message {
    font-size: 1rem;
    color: var(--muted-foreground);
    margin: 0 0 1.5rem;
}

.score-breakdown {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    padding: 1rem;
    background-color: var(--background);
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
}

.score-side {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.score-stone {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.score-stone.black-stone {
    background: radial-gradient(circle at 30% 30%, #4a4a4a, #1a1a1a);
}

.score-stone.white-stone {
    background: radial-gradient(circle at 30% 30%, #fff, #e0e0e0);
    border: 1px solid #ccc;
}

.score-info {
    text-align: center;
}

.score-label {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.score-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--foreground);
}

.score-detail {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.score-divider {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    font-weight: 500;
}

.modal-actions {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    font-size: 1rem;
    font-weight: 600;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
    border: none;
}

.btn-primary {
    background-color: var(--go-green);
    color: white;
}

.btn-primary:hover {
    background-color: var(--go-green-hover);
}

/* Controls Bar */
.controls-bar {
    display: flex;
    justify-content: center;
    padding: 1rem;
    background-color: var(--background-deep);
    border-top: 1px solid var(--border);
}
</style>
