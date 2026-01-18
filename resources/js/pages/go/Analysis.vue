<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, AlertTriangle, Maximize2, Minimize2 } from 'lucide-vue-next';
import { ref, watch, computed, onMounted, onUnmounted, onErrorCaptured } from 'vue';

import AnalysisSettingsPanel from '@/components/go/analysis/AnalysisSettingsPanel.vue';
import AnalysisToolbar from '@/components/go/analysis/AnalysisToolbar.vue';
import AnnotationEditor from '@/components/go/analysis/AnnotationEditor.vue';
import BottomDrawer from '@/components/go/analysis/BottomDrawer.vue';
import BranchControls from '@/components/go/analysis/BranchControls.vue';
import EngineStatusPanel from '@/components/go/analysis/EngineStatusPanel.vue';
import MovesPanelTabs from '@/components/go/analysis/MovesPanelTabs.vue';
import MoveTable from '@/components/go/analysis/MoveTable.vue';
import ResizableRail from '@/components/go/analysis/ResizableRail.vue';
import StudySaveDialog from '@/components/go/analysis/StudySaveDialog.vue';
import VisualMoveTree from '@/components/go/analysis/VisualMoveTree.vue';
import WinRateGraph from '@/components/go/analysis/WinRateGraph.vue';
import GoBoard from '@/components/go/GoBoard.vue';
import { useAnalysisKeyboard } from '@/composables/go/useAnalysisKeyboard';
import { useAnalysisSettings } from '@/composables/go/useAnalysisSettings';
import { useAnalysisStudy } from '@/composables/go/useAnalysisStudy';
import { useAnalysisTree } from '@/composables/go/useAnalysisTree';
import { useGoAnalysis } from '@/composables/go/useGoAnalysis';
import type { AnalysisStudy, MoveTree } from '@/types/analysis';
import type { Coordinate } from '@/types/go';

// Props from Inertia
const props = defineProps<{
    boardSize?: 9 | 13 | 19;
    study?: AnalysisStudy;
}>();

// Validate and initialize board size
const VALID_BOARD_SIZES = [9, 13, 19] as const;
const rawBoardSize = props.study?.board_size ?? props.boardSize ?? 19;
const initialBoardSize: 9 | 13 | 19 = VALID_BOARD_SIZES.includes(rawBoardSize as 9 | 13 | 19)
    ? (rawBoardSize as 9 | 13 | 19)
    : 19;

// Validate and initialize komi (reasonable range: -10 to 50)
const rawKomi = props.study?.komi ?? 6.5;
const initialKomi = typeof rawKomi === 'number' && rawKomi >= -10 && rawKomi <= 50
    ? rawKomi
    : 6.5;

const tree = useAnalysisTree({
    boardSize: initialBoardSize,
    komi: initialKomi,
});

const analysis = useGoAnalysis();
const studyApi = useAnalysisStudy();
const analysisSettings = useAnalysisSettings();

// Error handling
const componentError = ref<string | null>(null);
const invalidMoveMessage = ref<string | null>(null);

onErrorCaptured((err) => {
    console.error('Analysis page error:', err);
    componentError.value = err instanceof Error ? err.message : 'An unexpected error occurred';
    return false; // Prevent error from propagating
});

// Clear invalid move message after delay
function showInvalidMove(message: string) {
    invalidMoveMessage.value = message;
    setTimeout(() => {
        invalidMoveMessage.value = null;
    }, 2000);
}

// Win rate history for graph
const winRateHistory = ref<{ moveNumber: number; blackWinRate: number; stone: 'black' | 'white' }[]>([]);

// Initialize tree from study if provided
// Warn user before leaving page with unsaved changes
function handleBeforeUnload(event: BeforeUnloadEvent) {
    if (hasUnsavedChanges.value) {
        event.preventDefault();
        // Chrome requires returnValue to be set
        event.returnValue = '';
        return '';
    }
}

onMounted(() => {
    if (props.study?.move_tree) {
        tree.initializeTree(props.study.move_tree as MoveTree);
    }
    window.addEventListener('beforeunload', handleBeforeUnload);
});

// Cleanup on unmount
onUnmounted(() => {
    analysis.cancelPendingAnalysis();
    window.removeEventListener('beforeunload', handleBeforeUnload);
});

// Dialog state
const showSaveDialog = ref(false);
const showHelpDialog = ref(false);

// Focus board mode (collapse sidebar)
const isFocusMode = ref(false);

function toggleFocusMode() {
    isFocusMode.value = !isFocusMode.value;
}

// Bottom drawer tabs for mobile
const drawerTabs = [
    { id: 'moves', label: 'Move List' },
    { id: 'tree', label: 'Move Tree' },
];

// Rail width state
const railWidth = ref(360);
function handleRailResize(width: number) {
    railWidth.value = width;
}

// Hovered move coordinate (from move tree)
const hoveredCoordinate = ref<Coordinate | null>(null);

// Current study state
const currentStudy = ref<AnalysisStudy | null>(props.study ?? null);
const studyTitle = ref(props.study?.title ?? 'New Analysis');
const isPublic = ref(props.study?.is_public ?? false);

// Track unsaved changes - watch specific indicators instead of deep watching entire tree
const hasUnsavedChanges = ref(false);

// Watch for tree structure changes (new moves, deletions, annotations)
watch(
    [
        () => tree.totalMoveCount.value,
        () => tree.currentNode.value?.comment,
        () => tree.currentNode.value?.symbols,
    ],
    () => {
        hasUnsavedChanges.value = true;
    }
);

// Auto-analyze after navigation and track win rate history
watch(
    () => tree.currentNode.value,
    async (node) => {
        if (!node) return; // Guard against undefined node

        if (tree.board.value && analysisSettings.settings.value.autoAnalyze) {
            // Clear old suggestions IMMEDIATELY (sync, before async call)
            // This ensures old suggestions disappear right away while new analysis computes
            analysis.clearAnalysis();

            // Capture node info before async call to prevent race conditions
            const capturedMoveNumber = node.moveNumber;
            const capturedStone = node.stone;

            try {
                const result = await analysis.analyzePosition(
                    tree.board.value,
                    tree.currentPlayer.value,
                    initialBoardSize,
                    analysisSettings.apiSettings.value
                );

                // Only update history if analysis completed and move number is valid
                // Result is null if request was aborted (user navigated away)
                if (result && capturedMoveNumber > 0) {
                    const existingIndex = winRateHistory.value.findIndex(h => h.moveNumber === capturedMoveNumber);
                    const historyEntry = {
                        moveNumber: capturedMoveNumber,
                        blackWinRate: result.winRate.black,
                        stone: capturedStone,
                    };

                    if (existingIndex >= 0) {
                        winRateHistory.value[existingIndex] = historyEntry;
                    } else {
                        const insertIndex = winRateHistory.value.findIndex(h => h.moveNumber > capturedMoveNumber);
                        if (insertIndex === -1) {
                            winRateHistory.value.push(historyEntry);
                        } else {
                            winRateHistory.value.splice(insertIndex, 0, historyEntry);
                        }
                    }
                }
            } catch (e) {
                console.warn('Auto-analyze failed:', e);
            }
        }
    }
);

// Initial analysis (with error handling)
if (tree.board.value) {
    analysis.analyzePosition(tree.board.value, tree.currentPlayer.value, initialBoardSize, analysisSettings.apiSettings.value)
        .catch((e) => console.warn('Initial analysis failed:', e));
}

// Handle player move
function handlePlay(coord: Coordinate) {
    // Clear suggestions immediately before playing move
    // This provides instant visual feedback while new analysis computes
    analysis.clearAnalysis();

    const success = tree.playMove(coord);
    if (!success) {
        // Check if position is occupied or move is otherwise invalid
        const stone = tree.board.value?.[coord.y]?.[coord.x];
        if (stone) {
            showInvalidMove('Position is occupied');
        } else {
            showInvalidMove('Invalid move (suicide or ko)');
        }
    }
}

// Handle pass
function handlePass() {
    analysis.clearAnalysis();
    tree.pass();
}

// Navigation handlers - clear suggestions immediately for instant feedback
function handleGoBack() { analysis.clearAnalysis(); tree.goToParent(); }
function handleGoForward() { analysis.clearAnalysis(); tree.goToChild(0); }
function handleGoStart() { analysis.clearAnalysis(); tree.goToRoot(); }
function handleGoEnd() { analysis.clearAnalysis(); tree.goToEndOfLine(); }
function handlePrevVariation() { analysis.clearAnalysis(); tree.goToPrevSibling(); }
function handleNextVariation() { analysis.clearAnalysis(); tree.goToNextSibling(); }

// Delete branch with confirmation
function handleDeleteBranch() {
    if (tree.currentNode.value.id === 'root') return;
    const confirmed = window.confirm('Delete this branch and all its variations? This cannot be undone.');
    if (confirmed) {
        tree.deleteNode(tree.currentNode.value.id);
    }
}

// Move selection from list
function handleSelectMove(nodeId: string) {
    analysis.clearAnalysis();
    tree.jumpToNode(nodeId);
}

// Handle selecting move from graph
function handleSelectMoveNumber(moveNumber: number) {
    analysis.clearAnalysis();
    for (const nodeId of Object.keys(tree.tree.value.nodes)) {
        const node = tree.tree.value.nodes[nodeId];
        if (node.moveNumber === moveNumber) {
            tree.jumpToNode(nodeId);
            return;
        }
    }
}

// Handle hover on move in tree
function handleHoverMove(coordinate: Coordinate | null) {
    hoveredCoordinate.value = coordinate;
}

// Save handlers
function handleSave() {
    if (currentStudy.value) {
        saveStudy();
    } else {
        showSaveDialog.value = true;
    }
}

async function saveStudy() {
    const treeData = tree.serialize();
    if (currentStudy.value) {
        const result = await studyApi.updateStudy(currentStudy.value.id, {
            title: studyTitle.value,
            description: null,
            move_tree: treeData,
            is_public: isPublic.value,
        });
        if (result) {
            hasUnsavedChanges.value = false;
        } else {
            // Show error to user - studyApi.error already set
            componentError.value = studyApi.error.value ?? 'Failed to save study';
        }
    }
}

async function handleSaveNew(data: { title: string; isPublic: boolean }) {
    studyTitle.value = data.title;
    isPublic.value = data.isPublic;
    const treeData = tree.serialize();
    const result = await studyApi.createStudy({
        title: data.title,
        description: null,
        board_size: initialBoardSize,
        komi: initialKomi,
        move_tree: treeData,
        is_public: data.isPublic,
    });
    if (result) {
        currentStudy.value = { ...result, move_tree: treeData } as AnalysisStudy;
        hasUnsavedChanges.value = false;
        showSaveDialog.value = false;
        router.visit(`/go/studies/${result.id}`, { preserveState: true });
    } else {
        // Show error to user - studyApi.error already set
        componentError.value = studyApi.error.value ?? 'Failed to create study';
    }
}

// Handle back navigation
function handleBack() {
    if (hasUnsavedChanges.value) {
        const confirmed = window.confirm('You have unsaved changes. Are you sure you want to leave?');
        if (!confirmed) return;
    }
    router.visit('/go');
}

// Reset to new analysis
function handleNewAnalysis() {
    if (hasUnsavedChanges.value) {
        const confirmed = window.confirm('You have unsaved changes. Start a new analysis anyway?');
        if (!confirmed) return;
    }
    tree.reset();
    currentStudy.value = null;
    studyTitle.value = 'New Analysis';
    isPublic.value = false;
    hasUnsavedChanges.value = false;
    winRateHistory.value = [];
    analysis.clearAnalysis();
    analysis.analyzePosition(tree.board.value, tree.currentPlayer.value, initialBoardSize, analysisSettings.apiSettings.value);
}

// Keyboard navigation
useAnalysisKeyboard({
    onGoBack: handleGoBack,
    onGoForward: handleGoForward,
    onGoStart: handleGoStart,
    onGoEnd: handleGoEnd,
    onPrevVariation: handlePrevVariation,
    onNextVariation: handleNextVariation,
    onPass: handlePass,
    onDelete: handleDeleteBranch,
    onShowHelp: () => { showHelpDialog.value = true; },
    onToggleFocus: toggleFocusMode,
});

// Page title
const pageTitle = computed(() => {
    if (currentStudy.value) {
        return `${studyTitle.value} - Analysis`;
    }
    return 'Analysis Mode';
});

// Convert coordinate to display format (e.g., D4)
function coordToLabel(coord: { x: number; y: number }): string {
    const letters = 'ABCDEFGHJKLMNOPQRST'; // Skip 'I' in Go
    const col = letters[coord.x] ?? '?';
    const row = initialBoardSize - coord.y;
    return `${col}${row}`;
}

// Turn indicator text
const turnLabel = computed(() => {
    return tree.currentPlayer.value === 'black' ? 'Black to play' : 'White to play';
});
</script>

<template>
    <Head :title="pageTitle" />

    <!-- Error Display -->
    <div v-if="componentError" class="error-banner">
        <AlertTriangle :size="18" />
        <span>{{ componentError }}</span>
        <button @click="componentError = null">Dismiss</button>
    </div>

    <div class="analysis-page">
        <!-- Analysis Progress Bar -->
        <div class="analysis-progress" :class="{ active: analysis.isAnalyzing.value }"></div>

        <!-- Header -->
        <header class="analysis-header">
            <button class="exit-btn" @click="handleBack">
                <ArrowLeft :size="18" />
                <span>Exit</span>
            </button>

            <AnalysisToolbar
                v-model:title="studyTitle"
                :is-saving="studyApi.isSaving.value"
                :has-unsaved-changes="hasUnsavedChanges"
                :is-new="!currentStudy"
                @save="handleSave"
                @new="handleNewAnalysis"
            />

            <div class="header-info">
                <button
                    class="focus-toggle"
                    :class="{ active: isFocusMode }"
                    title="Focus Board (F)"
                    @click="toggleFocusMode"
                >
                    <Minimize2 v-if="isFocusMode" :size="18" />
                    <Maximize2 v-else :size="18" />
                </button>
                <span class="board-size">{{ initialBoardSize }}x{{ initialBoardSize }}</span>
                <span class="mode-badge">Analysis</span>
            </div>
        </header>

        <!-- Main Content -->
        <main class="analysis-main" :class="{ focused: isFocusMode }">
            <!-- Left: Board -->
            <section class="board-section">
                <GoBoard
                    :board="tree.board.value"
                    :size="initialBoardSize"
                    :current-player="tree.currentPlayer.value"
                    :last-move="tree.lastMove.value"
                    :disabled="false"
                    :suggested-moves="analysis.suggestedMoves.value"
                    :show-suggestions="analysisSettings.settings.value.autoAnalyze"
                    :hovered-coordinate="hoveredCoordinate"
                    @play="handlePlay"
                />

                <!-- Invalid move notification -->
                <div v-if="invalidMoveMessage" class="invalid-move-toast">
                    {{ invalidMoveMessage }}
                </div>

                <BranchControls
                    :can-go-back="tree.canGoBack.value"
                    :can-go-forward="tree.canGoForward.value"
                    :current-move-number="tree.currentMoveNumber.value"
                    @go-back="handleGoBack"
                    @go-forward="handleGoForward"
                    @go-start="handleGoStart"
                    @go-end="handleGoEnd"
                />
            </section>

            <!-- Middle: Analysis Sidebar -->
            <ResizableRail
                :collapsed="isFocusMode"
                :initial-width="railWidth"
                @resize="handleRailResize"
            >
                <!-- Engine Status -->
                <EngineStatusPanel
                    :is-analyzing="analysis.isAnalyzing.value"
                />

                <!-- Analysis Settings -->
                <AnalysisSettingsPanel
                    :settings="analysisSettings.settings.value"
                    :current-preset="analysisSettings.currentPreset.value"
                    :presets="analysisSettings.presets"
                    @apply-preset="analysisSettings.applyPreset"
                    @update:auto-analyze="analysisSettings.setAutoAnalyze"
                />

                <!-- Game State -->
                <div class="panel game-state">
                    <div class="turn-badge" :class="tree.currentPlayer.value">
                        {{ turnLabel }}
                    </div>
                    <div class="captures">
                        <span class="cap"><span class="dot black"></span>{{ tree.blackCaptures.value }}</span>
                        <span class="cap"><span class="dot white"></span>{{ tree.whiteCaptures.value }}</span>
                    </div>
                </div>

                <!-- Top Moves -->
                <div class="panel">
                    <div class="panel-header">
                        <span class="panel-title">Top Moves</span>
                        <Loader2 v-if="analysis.isAnalyzing.value" :size="14" class="spin" />
                    </div>
                    <div v-if="analysis.isAnalyzing.value && !analysis.analysis.value?.topMoves?.length" class="top-moves-loading">
                        Calculating...
                    </div>
                    <div v-else-if="analysis.analysis.value?.topMoves?.length" class="top-moves-list">
                        <div
                            v-for="(move, index) in analysis.analysis.value.topMoves"
                            :key="index"
                            class="move-item"
                            :class="{ best: index === 0 }"
                        >
                            <span class="move-rank">{{ move.rank }}</span>
                            <span class="move-coord">{{ coordToLabel(move.coordinate) }}</span>
                            <span class="move-winrate">{{ move.winRate.toFixed(1) }}%</span>
                        </div>
                    </div>
                    <div v-else class="no-moves">
                        No analysis available
                    </div>
                </div>

                <!-- Win Rate Graph -->
                <WinRateGraph
                    :history="winRateHistory"
                    :current-move-number="tree.currentMoveNumber.value"
                    @select-move="handleSelectMoveNumber"
                />

                <!-- Annotations -->
                <AnnotationEditor
                    :comment="tree.currentNode.value.comment"
                    :symbols="tree.currentNode.value.symbols"
                    :disabled="tree.currentNode.value.id === 'root'"
                    @update:comment="tree.setComment"
                    @toggle-symbol="tree.toggleSymbol"
                />
            </ResizableRail>

            <!-- Right: Moves Panel (far right) -->
            <aside v-if="!isFocusMode" class="moves-rail">
                <MovesPanelTabs
                    :nodes="tree.tree.value.nodes"
                    :root-id="tree.tree.value.rootId"
                    :current-node-id="tree.currentNode.value.id"
                    :board-size="initialBoardSize"
                    :win-rate-history="winRateHistory"
                    @select-node="handleSelectMove"
                    @hover-move="handleHoverMove"
                />
            </aside>
        </main>

        <!-- Mobile Bottom Drawer -->
        <BottomDrawer :tabs="drawerTabs" default-tab="moves">
            <template #moves>
                <MoveTable
                    :nodes="tree.tree.value.nodes"
                    :root-id="tree.tree.value.rootId"
                    :current-node-id="tree.currentNode.value.id"
                    :board-size="initialBoardSize"
                    :win-rate-history="winRateHistory"
                    @select-node="handleSelectMove"
                    @hover-move="handleHoverMove"
                />
            </template>
            <template #tree>
                <VisualMoveTree
                    :nodes="tree.tree.value.nodes"
                    :root-id="tree.tree.value.rootId"
                    :current-node-id="tree.currentNode.value.id"
                    :board-size="initialBoardSize"
                    @select-node="handleSelectMove"
                    @hover-move="handleHoverMove"
                />
            </template>
        </BottomDrawer>

        <!-- Save Dialog -->
        <StudySaveDialog
            v-if="showSaveDialog"
            :initial-title="studyTitle"
            :initial-is-public="isPublic"
            :is-saving="studyApi.isSaving.value"
            @save="handleSaveNew"
            @cancel="showSaveDialog = false"
        />

        <!-- Keyboard Shortcuts Help Dialog -->
        <div v-if="showHelpDialog" class="help-overlay" @click.self="showHelpDialog = false">
            <div class="help-dialog">
                <div class="help-header">
                    <h3>Keyboard Shortcuts</h3>
                    <button class="close-btn" @click="showHelpDialog = false">&times;</button>
                </div>
                <div class="help-content">
                    <div class="shortcut-group">
                        <h4>Navigation</h4>
                        <div class="shortcut"><kbd>&larr;</kbd> Previous move</div>
                        <div class="shortcut"><kbd>&rarr;</kbd> Next move</div>
                        <div class="shortcut"><kbd>Home</kbd> Go to start</div>
                        <div class="shortcut"><kbd>End</kbd> Go to end</div>
                    </div>
                    <div class="shortcut-group">
                        <h4>Variations</h4>
                        <div class="shortcut"><kbd>&uarr;</kbd> Previous variation</div>
                        <div class="shortcut"><kbd>&darr;</kbd> Next variation</div>
                    </div>
                    <div class="shortcut-group">
                        <h4>Actions</h4>
                        <div class="shortcut"><kbd>P</kbd> Pass</div>
                        <div class="shortcut"><kbd>F</kbd> Focus Board</div>
                        <div class="shortcut"><kbd>Ctrl</kbd>+<kbd>Del</kbd> Delete branch</div>
                        <div class="shortcut"><kbd>?</kbd> Show this help</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Error Banner */
.error-banner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background-color: #fef2f2;
    border-bottom: 1px solid #fecaca;
    color: #dc2626;
    font-size: 0.875rem;
}

.error-banner button {
    margin-left: auto;
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
    color: #dc2626;
    background: transparent;
    border: 1px solid #dc2626;
    border-radius: 0.25rem;
    cursor: pointer;
}

.error-banner button:hover {
    background-color: #dc2626;
    color: white;
}

/* Invalid move toast */
.invalid-move-toast {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    padding: 0.75rem 1.25rem;
    background-color: rgba(239, 68, 68, 0.95);
    color: white;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.5rem;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    z-index: 100;
    animation: toast-fade 2s ease-in-out;
    pointer-events: none;
}

@keyframes toast-fade {
    0%, 70% { opacity: 1; }
    100% { opacity: 0; }
}

.analysis-page {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background-color: var(--background);
    position: relative;
}

/* Analysis Progress Bar */
.analysis-progress {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background-color: transparent;
    overflow: hidden;
    z-index: 50;
}

.analysis-progress.active {
    background-color: rgba(74, 163, 163, 0.1);
}

.analysis-progress.active::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 30%;
    background: linear-gradient(90deg, transparent, var(--accent-analyze), transparent);
    animation: progress-slide 1.5s ease-in-out infinite;
}

@keyframes progress-slide {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(400%); }
}

/* Header */
.analysis-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1.5rem;
    padding: 0.875rem 1.5rem;
    background-color: var(--card);
    border-bottom: 1px solid var(--border);
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
}

.exit-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--muted-foreground);
    background: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.15s ease;
}

.exit-btn:hover {
    color: var(--foreground);
    background-color: var(--accent);
    border-color: var(--foreground);
}

.header-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.board-size {
    padding: 0.375rem 0.75rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: var(--muted-foreground);
    background-color: var(--background);
    border-radius: 0.375rem;
}

.focus-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    background: var(--background);
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    color: var(--muted-foreground);
    cursor: pointer;
    transition: all 0.15s ease;
}

.focus-toggle:hover {
    color: var(--foreground);
    background-color: var(--accent);
    border-color: var(--foreground);
}

.focus-toggle.active {
    color: var(--accent-analyze);
    border-color: var(--accent-analyze);
    background-color: rgba(74, 163, 163, 0.1);
}

.mode-badge {
    padding: 0.375rem 0.875rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    color: var(--accent-analyze);
    background-color: rgba(74, 163, 163, 0.1);
    border: 1px solid rgba(74, 163, 163, 0.3);
    border-radius: 1rem;
}

/* Main Layout */
.analysis-main {
    flex: 1;
    display: flex;
    gap: 1rem;
    padding: 1rem 1.5rem;
    padding-bottom: 60px; /* Space for mobile drawer */
    overflow: hidden; /* Prevent main from scrolling */
    background-color: var(--background-deep);
    transition: padding 0.2s ease;
    min-height: 0; /* Allow flexbox children to shrink */
}

.analysis-main.focused {
    justify-content: center;
}

@media (max-width: 1200px) {
    .analysis-main {
        flex-direction: column;
        align-items: center;
        overflow: auto;
    }
}

@media (min-width: 1201px) {
    .analysis-main {
        padding-bottom: 1rem;
    }
}

/* Moves Rail (far right) */
.moves-rail {
    flex: 1;
    min-width: 280px;
    display: flex;
    flex-direction: column;
    min-height: 0;
}

@media (max-width: 1200px) {
    .moves-rail {
        display: none;
    }
}

/* Board Section */
.board-section {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.75rem;
    flex-shrink: 0;
}

/* Lighter panels without heavy borders */
.panel {
    padding: 0.75rem 1rem;
    background-color: var(--card);
    border-radius: 0.5rem;
    /* Subtle shadow instead of border */
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
}

.panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.panel-title {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--muted-foreground);
}

/* Game State */
.game-state {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.turn-badge {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
}

.turn-badge.black {
    background-color: #1a1a1a;
    color: #fff;
}

.turn-badge.white {
    background-color: #f5f5f5;
    color: #1a1a1a;
    border: 1px solid var(--border);
}

.captures {
    display: flex;
    gap: 0.75rem;
}

.cap {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    font-weight: 600;
}

.dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
}

.dot.black { background: #1a1a1a; }
.dot.white { background: #f5f5f5; border: 1px solid #ccc; }

/* Top Moves - Enhanced with prominent win rates */
.top-moves-list {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.move-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.625rem 0.75rem;
    background-color: var(--background);
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.15s ease;
    cursor: pointer;
}

.move-item:hover {
    background-color: var(--accent);
    transform: translateX(2px);
}

.move-item.best {
    background: linear-gradient(135deg, rgba(34, 197, 94, 0.15) 0%, rgba(34, 197, 94, 0.08) 100%);
    border-left: 3px solid #22c55e;
}

.move-rank {
    width: 1.75rem;
    height: 1.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: var(--muted);
    border-radius: 50%;
    font-size: 0.75rem;
    font-weight: 700;
    color: var(--foreground);
    flex-shrink: 0;
}

.move-item.best .move-rank {
    background-color: #22c55e;
    color: white;
    box-shadow: 0 2px 4px rgba(34, 197, 94, 0.3);
}

.move-coord {
    font-weight: 600;
    font-family: monospace;
    font-size: 0.9375rem;
    color: var(--foreground);
}

/* Win rate is now the dominant visual element */
.move-winrate {
    margin-left: auto;
    font-size: 1.125rem;
    font-weight: 700;
    font-family: monospace;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    background-color: rgba(0, 0, 0, 0.05);
}

/* Color gradient based on win rate */
.move-item.best .move-winrate {
    color: #16a34a;
}

.move-item:not(.best) .move-winrate {
    color: var(--muted-foreground);
}

.top-moves-loading,
.no-moves {
    text-align: center;
    padding: 1rem;
    color: var(--muted-foreground);
    font-size: 0.875rem;
}

/* Animations */
.spin {
    animation: spin 1s linear infinite;
    color: var(--muted-foreground);
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

/* Help Dialog */
.help-overlay {
    position: fixed;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: rgba(0, 0, 0, 0.5);
    z-index: 100;
}

.help-dialog {
    background-color: var(--card);
    border: 1px solid var(--border);
    border-radius: 0.75rem;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    max-width: 400px;
    width: 90%;
}

.help-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid var(--border);
}

.help-header h3 {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
    color: var(--foreground);
}

.close-btn {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: var(--muted-foreground);
    cursor: pointer;
    padding: 0;
    line-height: 1;
}

.close-btn:hover {
    color: var(--foreground);
}

.help-content {
    padding: 1rem 1.25rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.shortcut-group h4 {
    margin: 0 0 0.5rem 0;
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.shortcut {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: var(--foreground);
    padding: 0.25rem 0;
}

kbd {
    display: inline-block;
    padding: 0.125rem 0.375rem;
    font-size: 0.75rem;
    font-family: inherit;
    background-color: var(--muted);
    border: 1px solid var(--border);
    border-radius: 0.25rem;
    box-shadow: 0 1px 0 var(--border);
}
</style>
