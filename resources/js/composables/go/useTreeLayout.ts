import { computed, type Ref } from 'vue';

import type { MoveNode } from '@/types/analysis';

export interface LayoutNode {
    id: string;
    node: MoveNode;
    x: number;           // Horizontal position (column index)
    y: number;           // Vertical position (row index for variations)
    isMainLine: boolean;
    parentLayoutNode: LayoutNode | null;
}

export interface TreeLayoutResult {
    layoutNodes: LayoutNode[];
    maxX: number;
    maxY: number;
}

/**
 * Composable for computing visual tree layout positions
 */
export function useTreeLayout(
    nodes: Ref<Record<string, MoveNode>>,
    rootId: Ref<string>
) {
    const layout = computed<TreeLayoutResult>(() => {
        return computeLayout(nodes.value, rootId.value);
    });

    return {
        layout,
        layoutNodes: computed(() => layout.value.layoutNodes),
        maxX: computed(() => layout.value.maxX),
        maxY: computed(() => layout.value.maxY),
    };
}

/**
 * Compute layout positions for all nodes in the tree
 * - Main line flows horizontally (left-to-right)
 * - Variations stack vertically below
 */
function computeLayout(
    nodes: Record<string, MoveNode>,
    rootId: string
): TreeLayoutResult {
    const layoutNodes: LayoutNode[] = [];
    const layoutMap = new Map<string, LayoutNode>();

    // Track the maximum y used at each x position to avoid overlaps
    const maxYAtX = new Map<number, number>();

    let maxX = 0;
    let maxY = 0;

    function getMaxYAtX(x: number): number {
        return maxYAtX.get(x) ?? -1;
    }

    function setMaxYAtX(x: number, y: number): void {
        const current = maxYAtX.get(x) ?? -1;
        if (y > current) {
            maxYAtX.set(x, y);
        }
    }

    /**
     * Recursively traverse the tree and assign positions
     * @param nodeId - Current node ID
     * @param x - Column position (move index in the line)
     * @param baseY - Base row for this branch
     * @param isMainLine - Whether this node is on the main line
     * @param parentLayout - Parent's layout node
     */
    function traverse(
        nodeId: string,
        x: number,
        baseY: number,
        isMainLine: boolean,
        parentLayout: LayoutNode | null
    ): void {
        const node = nodes[nodeId];
        if (!node) return;

        // Skip root node in display but process its children
        if (nodeId === rootId) {
            // Process children of root
            const children = node.children;
            if (children.length > 0) {
                // First child continues as main line
                traverse(children[0], 0, 0, true, null);

                // Additional children are variations
                let variationY = 1;
                for (let i = 1; i < children.length; i++) {
                    // Find safe Y position that doesn't overlap with existing nodes
                    const safeY = Math.max(variationY, getMaxYAtX(0) + 1);
                    traverse(children[i], 0, safeY, false, null);
                    variationY = safeY + 1;
                }
            }
            return;
        }

        // Determine Y position - ensure no overlap
        let y = baseY;
        // For variations, check if this Y is already occupied at this X
        if (!isMainLine) {
            const currentMaxY = getMaxYAtX(x);
            if (y <= currentMaxY) {
                y = currentMaxY + 1;
            }
        }

        // Create layout node
        const layoutNode: LayoutNode = {
            id: nodeId,
            node,
            x,
            y,
            isMainLine,
            parentLayoutNode: parentLayout,
        };

        layoutNodes.push(layoutNode);
        layoutMap.set(nodeId, layoutNode);

        // Update tracking
        setMaxYAtX(x, y);
        maxX = Math.max(maxX, x);
        maxY = Math.max(maxY, y);

        // Process children
        const children = node.children;
        if (children.length === 0) return;

        // First child continues horizontally (main line of this branch)
        traverse(children[0], x + 1, y, isMainLine, layoutNode);

        // Additional children branch vertically (variations)
        for (let i = 1; i < children.length; i++) {
            // Find safe Y position for the variation
            // Start from parent's Y + 1, but check all columns this variation will span
            let variationBaseY = y + 1;

            // Check what the max Y is at the next column position
            const nextColMaxY = getMaxYAtX(x + 1);
            if (variationBaseY <= nextColMaxY) {
                variationBaseY = nextColMaxY + 1;
            }

            traverse(children[i], x + 1, variationBaseY, false, layoutNode);
        }
    }

    // Start traversal from root
    traverse(rootId, 0, 0, true, null);

    return {
        layoutNodes,
        maxX,
        maxY,
    };
}
