import { computed, ref, shallowRef } from 'vue';

import { createEmptyBoard } from '@/lib/go/board';
import {
    createInitialState,
    DEFAULT_CONFIG,
    playMove as gamePlayMove,
    pass as gamePass,
} from '@/lib/go/game';
import type { MoveNode, MoveTree, MoveSymbol } from '@/types/analysis';
import type { Coordinate, GameConfig, GameState, Stone, BoardState, Move } from '@/types/go';

export interface UseAnalysisTreeOptions {
    boardSize?: 9 | 13 | 19;
    komi?: number;
}

/**
 * Generate unique ID for move nodes
 */
function generateNodeId(): string {
    return `node_${Date.now()}_${Math.random().toString(36).substring(2, 9)}`;
}

/**
 * Create the root node (empty position)
 */
function createRootNode(): MoveNode {
    return {
        id: 'root',
        coordinate: null,
        stone: 'white', // Root represents position before first move (black plays first)
        captures: [],
        moveNumber: 0,
        parent: null,
        children: [],
        comment: null,
        symbols: [],
    };
}

/**
 * Create an empty move tree
 */
function createEmptyTree(): MoveTree {
    return {
        nodes: {
            root: createRootNode(),
        },
        rootId: 'root',
        currentNodeId: 'root',
    };
}

/**
 * Composable for managing the analysis move tree with variations
 */
export function useAnalysisTree(options: UseAnalysisTreeOptions = {}) {
    const config = ref<GameConfig>({
        ...DEFAULT_CONFIG,
        boardSize: options.boardSize ?? 19,
        komi: options.komi ?? 6.5,
    });

    const tree = ref<MoveTree>(createEmptyTree());

    // Forward path: remembers where we came from when going back
    // This allows "redo" behavior - going forward returns to where we were
    const forwardPath = ref<string[]>([]);

    // Rebuild game state from root to current node
    const gameState = computed<GameState>(() => {
        return rebuildStateToNode(tree.value.currentNodeId);
    });

    // Computed properties from game state
    const board = computed<BoardState>(() => gameState.value.board);
    const currentPlayer = computed<Stone>(() => gameState.value.currentPlayer);
    const blackCaptures = computed(() => gameState.value.blackCaptures);
    const whiteCaptures = computed(() => gameState.value.whiteCaptures);

    const currentNode = computed<MoveNode>(() => {
        return tree.value.nodes[tree.value.currentNodeId];
    });

    const currentMoveNumber = computed(() => currentNode.value.moveNumber);

    const lastMove = computed<Move | null>(() => {
        const node = currentNode.value;
        if (node.id === 'root') return null;
        return {
            coordinate: node.coordinate,
            stone: node.stone,
            captures: node.captures,
            moveNumber: node.moveNumber,
        };
    });

    // Navigation state
    const canGoBack = computed(() => currentNode.value.parent !== null);
    const canGoForward = computed(() => {
        // Can go forward if there's a saved forward path OR if there are children
        return forwardPath.value.length > 0 || currentNode.value.children.length > 0;
    });
    const hasVariations = computed(() => currentNode.value.children.length > 1);

    // Get the main line (path from root to end of current variation)
    const mainLine = computed<MoveNode[]>(() => {
        const nodes: MoveNode[] = [];
        let nodeId: string | null = tree.value.rootId;

        while (nodeId) {
            const node = tree.value.nodes[nodeId];
            if (!node) break;
            if (nodeId !== 'root') {
                nodes.push(node);
            }
            // Follow first child (main line)
            nodeId = node.children.length > 0 ? node.children[0] : null;
        }

        return nodes;
    });

    // Get current line (path from root to current node)
    const currentLine = computed<MoveNode[]>(() => {
        const nodes: MoveNode[] = [];
        let node: MoveNode | undefined = currentNode.value;

        while (node && node.id !== 'root') {
            nodes.unshift(node);
            node = node.parent ? tree.value.nodes[node.parent] : undefined;
        }

        return nodes;
    });

    // Get path from root to current node (for navigation display)
    const pathToCurrentNode = computed<string[]>(() => {
        return currentLine.value.map((n) => n.id);
    });

    /**
     * Rebuild game state by replaying moves from root to a specific node
     */
    function rebuildStateToNode(nodeId: string): GameState {
        // Collect path from root to node
        const path: MoveNode[] = [];
        let current: MoveNode | undefined = tree.value.nodes[nodeId];

        while (current && current.id !== 'root') {
            path.unshift(current);
            current = current.parent ? tree.value.nodes[current.parent] : undefined;
        }

        // Replay moves
        let state = createInitialState(config.value);

        for (const node of path) {
            if (node.coordinate === null) {
                const result = gamePass(state);
                state = result.state;
            } else {
                const result = gamePlayMove(state, node.coordinate);
                if (result.success) {
                    state = result.state;
                }
            }
        }

        return state;
    }

    /**
     * Find if a move already exists as a child of the current node
     */
    function findExistingMove(coord: Coordinate | null): string | null {
        const parent = currentNode.value;
        for (const childId of parent.children) {
            const child = tree.value.nodes[childId];
            if (coord === null && child.coordinate === null) {
                return childId;
            }
            if (
                coord &&
                child.coordinate &&
                coord.x === child.coordinate.x &&
                coord.y === child.coordinate.y
            ) {
                return childId;
            }
        }
        return null;
    }

    /**
     * Play a move (creates branch if it's an alternative move)
     */
    function playMove(coord: Coordinate): boolean {
        // Clear forward path when playing a new move
        forwardPath.value = [];

        // Check if this move already exists as a child
        const existingId = findExistingMove(coord);
        if (existingId) {
            // Navigate to existing move
            tree.value = {
                ...tree.value,
                currentNodeId: existingId,
            };
            return true;
        }

        // Validate the move
        const state = gameState.value;
        const result = gamePlayMove(state, coord);

        if (!result.success) {
            return false;
        }

        // Create new node
        const newNode: MoveNode = {
            id: generateNodeId(),
            coordinate: coord,
            stone: state.currentPlayer,
            captures: result.state.moveHistory[result.state.moveHistory.length - 1].captures,
            moveNumber: currentNode.value.moveNumber + 1,
            parent: currentNode.value.id,
            children: [],
            comment: null,
            symbols: [],
        };

        // Add to tree
        const updatedNodes = { ...tree.value.nodes };
        updatedNodes[newNode.id] = newNode;

        // Add as child of current node
        const parentNode = { ...updatedNodes[currentNode.value.id] };
        parentNode.children = [...parentNode.children, newNode.id];
        updatedNodes[parentNode.id] = parentNode;

        tree.value = {
            ...tree.value,
            nodes: updatedNodes,
            currentNodeId: newNode.id,
        };

        return true;
    }

    /**
     * Pass (play a pass move)
     */
    function pass(): boolean {
        // Clear forward path when playing a new move
        forwardPath.value = [];

        // Check if pass already exists as a child
        const existingId = findExistingMove(null);
        if (existingId) {
            tree.value = {
                ...tree.value,
                currentNodeId: existingId,
            };
            return true;
        }

        const state = gameState.value;

        // Create pass node
        const newNode: MoveNode = {
            id: generateNodeId(),
            coordinate: null,
            stone: state.currentPlayer,
            captures: [],
            moveNumber: currentNode.value.moveNumber + 1,
            parent: currentNode.value.id,
            children: [],
            comment: null,
            symbols: [],
        };

        // Add to tree
        const updatedNodes = { ...tree.value.nodes };
        updatedNodes[newNode.id] = newNode;

        const parentNode = { ...updatedNodes[currentNode.value.id] };
        parentNode.children = [...parentNode.children, newNode.id];
        updatedNodes[parentNode.id] = parentNode;

        tree.value = {
            ...tree.value,
            nodes: updatedNodes,
            currentNodeId: newNode.id,
        };

        return true;
    }

    // Navigation functions
    function goToNode(nodeId: string, clearForwardPath: boolean = false): boolean {
        if (!tree.value.nodes[nodeId]) {
            return false;
        }
        if (clearForwardPath) {
            forwardPath.value = [];
        }
        tree.value = { ...tree.value, currentNodeId: nodeId };
        return true;
    }

    function goToParent(): boolean {
        const parent = currentNode.value.parent;
        if (!parent) return false;

        // Remember where we came from so we can go back
        forwardPath.value = [currentNode.value.id, ...forwardPath.value];

        return goToNode(parent);
    }

    function goToChild(index: number = 0): boolean {
        // If we have a forward path, follow it (redo behavior)
        if (forwardPath.value.length > 0) {
            const nextId = forwardPath.value[0];
            forwardPath.value = forwardPath.value.slice(1);
            return goToNode(nextId);
        }

        // Otherwise, follow the specified child index
        const children = currentNode.value.children;
        if (children.length === 0 || index >= children.length) return false;
        return goToNode(children[index]);
    }

    function goToNextSibling(): boolean {
        const parent = currentNode.value.parent;
        if (!parent) return false;

        const parentNode = tree.value.nodes[parent];
        const currentIndex = parentNode.children.indexOf(currentNode.value.id);

        if (currentIndex < parentNode.children.length - 1) {
            // Clear forward path when switching variations
            forwardPath.value = [];
            return goToNode(parentNode.children[currentIndex + 1]);
        }
        return false;
    }

    function goToPrevSibling(): boolean {
        const parent = currentNode.value.parent;
        if (!parent) return false;

        const parentNode = tree.value.nodes[parent];
        const currentIndex = parentNode.children.indexOf(currentNode.value.id);

        if (currentIndex > 0) {
            // Clear forward path when switching variations
            forwardPath.value = [];
            return goToNode(parentNode.children[currentIndex - 1]);
        }
        return false;
    }

    function goToRoot(): boolean {
        // Clear forward path - this is an explicit jump
        forwardPath.value = [];
        return goToNode(tree.value.rootId);
    }

    function goToEndOfLine(): boolean {
        // Clear forward path - this is an explicit jump
        forwardPath.value = [];
        let node = currentNode.value;
        while (node.children.length > 0) {
            const nextNode = tree.value.nodes[node.children[0]];
            if (!nextNode) break; // Guard against missing node reference
            node = nextNode;
        }
        return goToNode(node.id);
    }

    function goToMove(moveNumber: number): boolean {
        // Clear forward path - this is an explicit jump
        forwardPath.value = [];

        if (moveNumber === 0) {
            return goToNode(tree.value.rootId);
        }

        // Find node in current line with this move number
        for (const node of currentLine.value) {
            if (node.moveNumber === moveNumber) {
                return goToNode(node.id);
            }
        }

        // Try main line if not in current line
        for (const node of mainLine.value) {
            if (node.moveNumber === moveNumber) {
                return goToNode(node.id);
            }
        }

        return false;
    }

    // Direct navigation to a specific node (e.g., from clicking in move list)
    function jumpToNode(nodeId: string): boolean {
        // Clear forward path - this is an explicit jump
        forwardPath.value = [];
        return goToNode(nodeId);
    }

    // Branch management
    function deleteNode(nodeId: string): boolean {
        if (nodeId === 'root') return false;

        const node = tree.value.nodes[nodeId];
        if (!node) return false;

        // Collect all descendants (BFS)
        const toDelete = new Set<string>([nodeId]);
        const queue = [...node.children];

        while (queue.length > 0) {
            const id = queue.shift()!;
            toDelete.add(id);
            const child = tree.value.nodes[id];
            if (child) {
                queue.push(...child.children);
            }
        }

        // Remove from parent's children
        const updatedNodes = { ...tree.value.nodes };

        if (node.parent) {
            const parentNode = { ...updatedNodes[node.parent] };
            parentNode.children = parentNode.children.filter((id) => id !== nodeId);
            updatedNodes[parentNode.id] = parentNode;
        }

        // Delete all nodes
        for (const id of toDelete) {
            delete updatedNodes[id];
        }

        // Update current node if deleted
        let newCurrentId = tree.value.currentNodeId;
        if (toDelete.has(newCurrentId)) {
            newCurrentId = node.parent || tree.value.rootId;
        }

        tree.value = {
            ...tree.value,
            nodes: updatedNodes,
            currentNodeId: newCurrentId,
        };

        return true;
    }

    function promoteVariation(nodeId: string): boolean {
        const node = tree.value.nodes[nodeId];
        if (!node || !node.parent) return false;

        const updatedNodes = { ...tree.value.nodes };
        const parentNode = { ...updatedNodes[node.parent] };

        const index = parentNode.children.indexOf(nodeId);
        if (index <= 0) return false; // Already first or not found

        // Move to front
        parentNode.children = [
            nodeId,
            ...parentNode.children.filter((id) => id !== nodeId),
        ];
        updatedNodes[parentNode.id] = parentNode;

        tree.value = {
            ...tree.value,
            nodes: updatedNodes,
        };

        return true;
    }

    // Annotation functions
    function setComment(comment: string | null): void {
        const updatedNodes = { ...tree.value.nodes };
        const node = { ...updatedNodes[tree.value.currentNodeId] };
        node.comment = comment && comment.trim() ? comment.trim() : null;
        updatedNodes[node.id] = node;

        tree.value = {
            ...tree.value,
            nodes: updatedNodes,
        };
    }

    function addSymbol(symbol: MoveSymbol): void {
        const updatedNodes = { ...tree.value.nodes };
        const node = { ...updatedNodes[tree.value.currentNodeId] };

        if (!node.symbols.includes(symbol)) {
            node.symbols = [...node.symbols, symbol];
            updatedNodes[node.id] = node;

            tree.value = {
                ...tree.value,
                nodes: updatedNodes,
            };
        }
    }

    function removeSymbol(symbol: MoveSymbol): void {
        const updatedNodes = { ...tree.value.nodes };
        const node = { ...updatedNodes[tree.value.currentNodeId] };

        node.symbols = node.symbols.filter((s) => s !== symbol);
        updatedNodes[node.id] = node;

        tree.value = {
            ...tree.value,
            nodes: updatedNodes,
        };
    }

    function toggleSymbol(symbol: MoveSymbol): void {
        if (currentNode.value.symbols.includes(symbol)) {
            removeSymbol(symbol);
        } else {
            addSymbol(symbol);
        }
    }

    function setAnalysis(analysis: { winRate: number; scoreEstimate: number }): void {
        const updatedNodes = { ...tree.value.nodes };
        const node = { ...updatedNodes[tree.value.currentNodeId] };
        node.analysis = analysis;
        updatedNodes[node.id] = node;

        tree.value = {
            ...tree.value,
            nodes: updatedNodes,
        };
    }

    // Serialization
    function serialize(): MoveTree {
        try {
            return JSON.parse(JSON.stringify(tree.value));
        } catch (e) {
            console.error('Failed to serialize tree:', e);
            // Return a shallow copy as fallback
            return { ...tree.value };
        }
    }

    function initializeTree(savedTree?: MoveTree): void {
        forwardPath.value = [];
        if (savedTree && savedTree.nodes && savedTree.rootId) {
            // Validate that root node exists and has expected structure
            const rootNode = savedTree.nodes[savedTree.rootId];
            if (rootNode && typeof rootNode === 'object' && Array.isArray(rootNode.children)) {
                tree.value = savedTree;
            } else {
                console.warn('Invalid saved tree structure, creating empty tree');
                tree.value = createEmptyTree();
            }
        } else {
            tree.value = createEmptyTree();
        }
    }

    function reset(newConfig?: Partial<GameConfig>): void {
        forwardPath.value = [];
        if (newConfig) {
            config.value = { ...config.value, ...newConfig };
        }
        tree.value = createEmptyTree();
    }

    // Import from move history (e.g., from a saved game)
    function importFromMoveHistory(moves: Move[]): void {
        reset();

        for (const move of moves) {
            if (move.coordinate === null) {
                pass();
            } else {
                playMove(move.coordinate);
            }
        }

        // Stay at the end of imported game (last move position)
        // No need to navigate - we're already at the last played position
    }

    // Get all nodes with variations (for tree display)
    const variationPoints = computed<MoveNode[]>(() => {
        return Object.values(tree.value.nodes).filter(
            (node) => node.children.length > 1
        );
    });

    // Get total move count in tree
    const totalMoveCount = computed(() => {
        return Object.keys(tree.value.nodes).length - 1; // Exclude root
    });

    // Get depth of current line
    const currentLineDepth = computed(() => currentLine.value.length);

    // Check if a move would be valid (without playing it)
    function isValidMove(coord: Coordinate): boolean {
        const result = gamePlayMove(gameState.value, coord);
        return result.success;
    }

    // Get sibling index (for showing which variation we're on)
    const currentVariationIndex = computed(() => {
        const parent = currentNode.value.parent;
        if (!parent) return 0;

        const parentNode = tree.value.nodes[parent];
        return parentNode.children.indexOf(currentNode.value.id);
    });

    const totalVariations = computed(() => {
        const parent = currentNode.value.parent;
        if (!parent) return 1;

        const parentNode = tree.value.nodes[parent];
        return parentNode.children.length;
    });

    return {
        // State
        config,
        tree,

        // Computed - Game state
        gameState,
        board,
        currentPlayer,
        blackCaptures,
        whiteCaptures,
        lastMove,
        currentMoveNumber,

        // Computed - Tree navigation
        currentNode,
        canGoBack,
        canGoForward,
        hasVariations,
        mainLine,
        currentLine,
        pathToCurrentNode,
        variationPoints,
        totalMoveCount,
        currentLineDepth,
        currentVariationIndex,
        totalVariations,

        // Actions - Play
        playMove,
        pass,
        isValidMove,

        // Actions - Navigation
        goToNode,
        goToParent,
        goToChild,
        goToNextSibling,
        goToPrevSibling,
        goToRoot,
        goToEndOfLine,
        goToMove,
        jumpToNode,

        // Actions - Branch management
        deleteNode,
        promoteVariation,

        // Actions - Annotations
        setComment,
        addSymbol,
        removeSymbol,
        toggleSymbol,
        setAnalysis,

        // Actions - Persistence
        serialize,
        initializeTree,
        reset,
        importFromMoveHistory,
    };
}
