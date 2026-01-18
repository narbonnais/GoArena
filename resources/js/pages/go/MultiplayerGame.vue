<script setup lang="ts">

import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Flag, Frown, Hand, Trophy, Wifi, WifiOff } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import GoBoard from '@/components/go/GoBoard.vue';
import { useMultiplayerGame } from '@/composables/go/useMultiplayerGame';
import type { Coordinate, Stone } from '@/types/go';
import type { GameEndedPayload, MultiplayerGame } from '@/types/multiplayer';

// Props from Inertia
const props = defineProps<{
    game: MultiplayerGame;
    playerColor: Stone | null;
    isSpectator: boolean;
}>();

// Initialize multiplayer game state
const multiplayer = useMultiplayerGame({
    game: props.game,
    playerColor: props.playerColor,
    onGameEnded: handleGameEnded,
});
const game = computed(() => multiplayer.game.value);

// Game result modal
const showResultModal = ref(false);
const gameResult = ref<GameEndedPayload | null>(null);

// Resign confirmation
const showResignConfirm = ref(false);

function handleGameEnded(payload: GameEndedPayload) {
    gameResult.value = payload;
    showResultModal.value = true;
}

// Handle player move
async function handlePlay(coord: Coordinate) {
    if (multiplayer.isScoring.value) {
        if (multiplayer.isSpectator.value) return;
        await multiplayer.toggleDeadStone(coord);
        return;
    }
    if (!multiplayer.isMyTurn.value) return;
    await multiplayer.playMove(coord);
}

// Handle pass
async function handlePass() {
    if (multiplayer.isScoring.value) return;
    if (!multiplayer.isMyTurn.value) return;
    await multiplayer.pass();
}

// Handle resign
async function handleResign() {
    showResignConfirm.value = false;
    await multiplayer.resign();
}

async function handleAcceptScore() {
    await multiplayer.acceptScore();
}

// Computed
const isBoardDisabled = computed(() => {
    return (
        multiplayer.isLoading.value ||
        multiplayer.isGameOver.value ||
        (!multiplayer.isMyTurn.value && !multiplayer.isScoring.value) ||
        (multiplayer.isScoring.value && multiplayer.isSpectator.value)
    );
});

const currentPlayerInfo = computed(() => {
    return multiplayer.game.value.current_player === 'black'
        ? multiplayer.game.value.black_player
        : multiplayer.game.value.white_player;
});

const scoreAcceptance = computed(() => multiplayer.scoreAcceptance.value ?? { black: false, white: false });
const provisionalScores = computed(() => multiplayer.provisionalScores.value);
const hasAcceptedScore = computed(() => {
    if (!props.playerColor) return false;
    return Boolean(scoreAcceptance.value?.[props.playerColor]);
});

const isDraw = computed(() => multiplayer.winner.value === 'draw');

const userWon = computed(() => {
    if (!multiplayer.isGameOver.value || !multiplayer.winner.value || isDraw.value) return null;
    return multiplayer.winner.value === props.playerColor;
});

const resultTitle = computed(() => {
    if (isDraw.value) {
        return 'Draw';
    }
    if (props.isSpectator) {
        return multiplayer.winner.value === 'black' ? 'Black Wins!' : 'White Wins!';
    }
    return userWon.value ? 'Victory!' : 'Defeat';
});

const resultMessage = computed(() => {
    if (!gameResult.value) return '';

    const reasonMap: Record<string, string> = {
        score: 'by score',
        resignation: 'by resignation',
        timeout: 'on time',
        abandonment: 'by abandonment',
    };

    const reason = reasonMap[gameResult.value.end_reason] || '';

    if (isDraw.value) {
        return reason ? `Draw ${reason}` : 'Draw';
    }

    if (props.isSpectator) {
        return `${gameResult.value.winner === 'black' ? 'Black' : 'White'} wins ${reason}`;
    }

    return userWon.value ? `You win ${reason}!` : `You lose ${reason}`;
});

function goBack() {
    router.visit('/go/multiplayer');
}
</script>

<template>
    <Head :title="`Game vs ${props.playerColor === 'black' ? game.white_player.name : game.black_player.name}`" />

    <div class="game-page">
        <!-- Header -->
        <header class="game-header">
            <button class="back-btn" @click="goBack">
                <ArrowLeft :size="18" />
                <span>Exit</span>
            </button>
            <div class="game-info">
                <span class="board-size">{{ multiplayer.boardSize.value }}x{{ multiplayer.boardSize.value }}</span>
                <span class="separator">|</span>
                <span class="time-control">{{ game.time_control.display_time }}</span>
                <span v-if="game.is_ranked" class="ranked-badge">Ranked</span>
            </div>
            <div class="connection-status">
                <WifiOff v-if="!multiplayer.opponentConnected.value" :size="18" class="disconnected" />
                <Wifi v-else :size="18" class="connected" />
            </div>
        </header>

        <!-- Main Game Area -->
        <main class="game-area">
            <!-- Black Player Panel -->
            <div class="player-panel" :class="{ active: game.current_player === 'black', 'is-you': playerColor === 'black' }">
                <div class="player-info">
                    <div class="player-avatar">
                        <div class="stone black-stone"></div>
                    </div>
                    <div class="player-details">
                        <span class="player-name">
                            {{ game.black_player.display_name || game.black_player.name }}
                            <span v-if="playerColor === 'black'" class="you-badge">You</span>
                        </span>
                        <span v-if="game.black_rating_before" class="player-rating">
                            {{ game.black_rating_before }}
                        </span>
                    </div>
                </div>
                <div class="timer" :class="{ low: multiplayer.blackTimeRemaining.value < 30000 }">
                    {{ multiplayer.blackTimeFormatted.value }}
                </div>
            </div>

            <!-- Board -->
            <div class="board-wrapper">
                <GoBoard
                    :board="multiplayer.board.value"
                    :size="multiplayer.boardSize.value"
                    :current-player="game.current_player"
                    :last-move="multiplayer.lastMove.value"
                    :disabled="isBoardDisabled"
                    :move-history="game.move_history"
                    :dead-stones="multiplayer.isScoring.value ? multiplayer.deadStones.value : []"
                    :interaction-mode="multiplayer.isScoring.value ? 'mark-dead' : 'play'"
                    @play="handlePlay"
                />
            </div>

            <!-- White Player Panel -->
            <div class="player-panel" :class="{ active: game.current_player === 'white', 'is-you': playerColor === 'white' }">
                <div class="player-info">
                    <div class="player-avatar">
                        <div class="stone white-stone"></div>
                    </div>
                    <div class="player-details">
                        <span class="player-name">
                            {{ game.white_player.display_name || game.white_player.name }}
                            <span v-if="playerColor === 'white'" class="you-badge">You</span>
                        </span>
                        <span v-if="game.white_rating_before" class="player-rating">
                            {{ game.white_rating_before }}
                        </span>
                    </div>
                </div>
                <div class="timer" :class="{ low: multiplayer.whiteTimeRemaining.value < 30000 }">
                    {{ multiplayer.whiteTimeFormatted.value }}
                </div>
            </div>
        </main>

        <!-- Controls -->
        <footer v-if="!isSpectator && !multiplayer.isGameOver.value && !multiplayer.isScoring.value" class="controls-bar">
            <button
                class="control-btn"
                :disabled="!multiplayer.isMyTurn.value || multiplayer.isLoading.value"
                @click="handlePass"
            >
                <Hand :size="18" />
                <span>Pass</span>
            </button>
            <button
                class="control-btn resign"
                :disabled="multiplayer.isLoading.value"
                @click="showResignConfirm = true"
            >
                <Flag :size="18" />
                <span>Resign</span>
            </button>
        </footer>

        <!-- Scoring Phase -->
        <section v-if="multiplayer.isScoring.value && !multiplayer.isGameOver.value" class="scoring-panel">
            <div class="scoring-header">
                <span class="scoring-title">Scoring Phase</span>
                <span class="scoring-subtitle">Mark dead stones by clicking them</span>
            </div>
            <div class="scoring-scores">
                <div class="score-side black">
                    <div class="score-label">Black</div>
                    <div class="score-value">{{ provisionalScores?.black ?? '—' }}</div>
                </div>
                <div class="score-divider">vs</div>
                <div class="score-side white">
                    <div class="score-label">White</div>
                    <div class="score-value">{{ provisionalScores?.white ?? '—' }}</div>
                </div>
            </div>
            <div class="score-acceptance">
                <span class="accept-status" :class="{ accepted: scoreAcceptance.black }">
                    Black {{ scoreAcceptance.black ? 'accepted' : 'pending' }}
                </span>
                <span class="accept-status" :class="{ accepted: scoreAcceptance.white }">
                    White {{ scoreAcceptance.white ? 'accepted' : 'pending' }}
                </span>
            </div>
            <div v-if="!isSpectator" class="scoring-actions">
                <button
                    class="control-btn"
                    :disabled="multiplayer.isLoading.value || hasAcceptedScore"
                    @click="handleAcceptScore"
                >
                    {{ hasAcceptedScore ? 'Accepted' : 'Accept Score' }}
                </button>
                <span v-if="hasAcceptedScore" class="waiting-note">Waiting for opponent</span>
            </div>
        </section>

        <!-- Spectator Notice -->
        <footer v-if="isSpectator" class="spectator-notice">
            You are spectating this game
        </footer>

        <!-- Resign Confirmation Modal -->
        <div v-if="showResignConfirm" class="modal-overlay">
            <div class="modal">
                <h2>Resign Game?</h2>
                <p>Are you sure you want to resign? This will count as a loss.</p>
                <div class="modal-actions">
                    <button class="btn btn-secondary" @click="showResignConfirm = false">Cancel</button>
                    <button class="btn btn-danger" @click="handleResign">Resign</button>
                </div>
            </div>
        </div>

        <!-- Game Result Modal -->
        <div v-if="showResultModal" class="modal-overlay">
            <div class="result-modal">
                <div class="result-icon" :class="{ win: userWon === true, loss: userWon === false, draw: isDraw }">
                    <Trophy v-if="userWon === true || isSpectator || isDraw" :size="48" />
                    <Frown v-else :size="48" />
                </div>
                <h2 class="result-title">{{ resultTitle }}</h2>
                <p class="result-message">{{ resultMessage }}</p>

                <!-- Rating Changes -->
                <div v-if="gameResult?.rating_changes && !isSpectator" class="rating-change">
                    <span class="rating-label">Rating Change:</span>
                    <span
                        class="rating-value"
                        :class="{
                            positive: (playerColor === 'black' ? gameResult.rating_changes.black.after - gameResult.rating_changes.black.before : gameResult.rating_changes.white.after - gameResult.rating_changes.white.before) > 0,
                            negative: (playerColor === 'black' ? gameResult.rating_changes.black.after - gameResult.rating_changes.black.before : gameResult.rating_changes.white.after - gameResult.rating_changes.white.before) < 0
                        }"
                    >
                        {{ playerColor === 'black'
                            ? (gameResult.rating_changes.black.after - gameResult.rating_changes.black.before > 0 ? '+' : '') + (gameResult.rating_changes.black.after - gameResult.rating_changes.black.before)
                            : (gameResult.rating_changes.white.after - gameResult.rating_changes.white.before > 0 ? '+' : '') + (gameResult.rating_changes.white.after - gameResult.rating_changes.white.before)
                        }}
                    </span>
                </div>

                <div class="modal-actions">
                    <button class="btn btn-primary" @click="goBack">Continue</button>
                </div>
            </div>
        </div>

        <!-- Error Toast -->
        <div v-if="multiplayer.error.value" class="error-toast" @click="multiplayer.clearError">
            {{ multiplayer.error.value }}
        </div>
    </div>
</template>

<style scoped>
.game-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background-color: var(--background);
}

.game-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    background-color: var(--background-deep);
    border-bottom: 1px solid var(--border);
}

.back-btn {
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

.back-btn:hover {
    color: var(--foreground);
    border-color: var(--foreground);
}

.game-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.875rem;
}

.board-size {
    font-weight: 700;
    color: var(--foreground);
}

.separator {
    color: var(--muted-foreground);
}

.time-control {
    color: var(--muted-foreground);
}

.ranked-badge {
    padding: 0.125rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--go-green);
    background-color: var(--go-green-muted);
    border-radius: 9999px;
}

.connection-status {
    display: flex;
    align-items: center;
}

.connection-status .connected {
    color: var(--go-green);
}

.connection-status .disconnected {
    color: hsl(0 84% 60%);
}

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
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem;
    background-color: var(--card);
    border: 2px solid var(--border);
    border-radius: 0.75rem;
    min-width: 200px;
    transition: all 0.15s ease;
}

.player-panel.active {
    border-color: var(--go-green);
    box-shadow: 0 0 0 1px var(--go-green-muted);
}

.player-panel.is-you {
    background-color: var(--go-green-muted);
}

.player-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}

.player-avatar {
    display: flex;
    align-items: center;
    justify-content: center;
}

.stone {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.black-stone {
    background: radial-gradient(circle at 30% 30%, #4a4a4a, #1a1a1a);
}

.white-stone {
    background: radial-gradient(circle at 30% 30%, #fff, #e0e0e0);
    border: 1px solid #ccc;
}

.player-details {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.player-name {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--foreground);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.you-badge {
    font-size: 0.625rem;
    font-weight: 600;
    padding: 0.125rem 0.375rem;
    background-color: var(--go-green);
    color: white;
    border-radius: 9999px;
}

.player-rating {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.timer {
    font-size: 1.5rem;
    font-weight: 700;
    font-variant-numeric: tabular-nums;
    color: var(--foreground);
}

.timer.low {
    color: hsl(0 84% 60%);
}

.board-wrapper {
    order: -1;
}

@media (min-width: 1024px) {
    .board-wrapper {
        order: 0;
    }
}

.controls-bar {
    display: flex;
    justify-content: center;
    gap: 1rem;
    padding: 1rem;
    background-color: var(--background-deep);
    border-top: 1px solid var(--border);
}

.scoring-panel {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    padding: 1rem;
    background-color: var(--background-deep);
    border-top: 1px solid var(--border);
}

.scoring-header {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.25rem;
}

.scoring-title {
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--foreground);
}

.scoring-subtitle {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.scoring-scores {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 1rem;
}

.score-side {
    text-align: center;
    min-width: 90px;
}

.score-label {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.score-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
}

.score-divider {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.score-acceptance {
    display: flex;
    justify-content: center;
    gap: 1rem;
    font-size: 0.75rem;
}

.accept-status {
    color: var(--muted-foreground);
}

.accept-status.accepted {
    color: var(--go-green);
    font-weight: 600;
}

.scoring-actions {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
}

.waiting-note {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.control-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--foreground);
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.control-btn:hover:not(:disabled) {
    background-color: var(--accent);
    border-color: var(--foreground);
}

.control-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.control-btn.resign {
    color: hsl(0 84% 60%);
    border-color: hsl(0 84% 60% / 0.3);
}

.control-btn.resign:hover:not(:disabled) {
    background-color: hsl(0 84% 60% / 0.1);
    border-color: hsl(0 84% 60%);
}

.spectator-notice {
    text-align: center;
    padding: 0.75rem;
    font-size: 0.875rem;
    color: var(--muted-foreground);
    background-color: var(--background-deep);
    border-top: 1px solid var(--border);
}

.modal-overlay {
    position: fixed;
    inset: 0;
    background-color: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 50;
    padding: 1rem;
}

.modal,
.result-modal {
    background-color: var(--card);
    border-radius: 1rem;
    padding: 2rem;
    max-width: 400px;
    width: 100%;
    text-align: center;
    border: 1px solid var(--border);
}

.modal h2,
.result-modal h2 {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--foreground);
    margin: 0 0 0.5rem;
}

.modal p {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0 0 1.5rem;
}

.modal-actions {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
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

.btn-secondary {
    background-color: var(--muted);
    color: var(--foreground);
}

.btn-secondary:hover {
    background-color: var(--accent);
}

.btn-danger {
    background-color: hsl(0 84% 60%);
    color: white;
}

.btn-danger:hover {
    background-color: hsl(0 84% 50%);
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
    margin: 0 0 0.5rem;
}

.result-message {
    font-size: 1rem;
    color: var(--muted-foreground);
    margin: 0 0 1rem;
}

.rating-change {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem;
    background-color: var(--background);
    border-radius: 0.5rem;
    margin-bottom: 1.5rem;
}

.rating-label {
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.rating-value {
    font-size: 1.25rem;
    font-weight: 700;
}

.rating-value.positive {
    color: var(--go-green);
}

.rating-value.negative {
    color: hsl(0 84% 60%);
}

.error-toast {
    position: fixed;
    bottom: 2rem;
    left: 50%;
    transform: translateX(-50%);
    padding: 0.75rem 1.5rem;
    font-size: 0.875rem;
    color: white;
    background-color: hsl(0 84% 60%);
    border-radius: 0.5rem;
    cursor: pointer;
    z-index: 100;
}
</style>
