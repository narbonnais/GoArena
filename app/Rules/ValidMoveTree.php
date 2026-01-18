<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidMoveTree implements ValidationRule
{
    /**
     * Maximum allowed nodes to prevent DoS attacks
     */
    protected int $maxNodes;

    public function __construct(int $maxNodes = 5000)
    {
        $this->maxNodes = $maxNodes;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            $fail('The :attribute must be an array.');

            return;
        }

        // Check required top-level keys
        if (! isset($value['nodes']) || ! is_array($value['nodes'])) {
            $fail('The :attribute must have a nodes array.');

            return;
        }

        if (! isset($value['rootId']) || ! is_string($value['rootId'])) {
            $fail('The :attribute must have a rootId string.');

            return;
        }

        if (! isset($value['currentNodeId']) || ! is_string($value['currentNodeId'])) {
            $fail('The :attribute must have a currentNodeId string.');

            return;
        }

        $nodes = $value['nodes'];
        $rootId = $value['rootId'];
        $currentNodeId = $value['currentNodeId'];

        // Check node count limit
        if (count($nodes) > $this->maxNodes) {
            $fail("The :attribute cannot have more than {$this->maxNodes} nodes.");

            return;
        }

        // Check rootId exists in nodes
        if (! array_key_exists($rootId, $nodes)) {
            $fail('The rootId must reference an existing node.');

            return;
        }

        // Check currentNodeId exists in nodes
        if (! array_key_exists($currentNodeId, $nodes)) {
            $fail('The currentNodeId must reference an existing node.');

            return;
        }

        // Validate each node structure
        $validStones = ['black', 'white'];
        foreach ($nodes as $nodeId => $node) {
            // Node must be an array
            if (! is_array($node)) {
                $fail("Node '{$nodeId}' must be an array.");

                return;
            }

            // Required fields
            if (! isset($node['id']) || ! is_string($node['id'])) {
                $fail("Node '{$nodeId}' must have an 'id' string field.");

                return;
            }

            // Node ID must match its key
            if ($node['id'] !== $nodeId) {
                $fail("Node '{$nodeId}' has mismatched id field.");

                return;
            }

            // Stone must be valid
            if (! isset($node['stone']) || ! in_array($node['stone'], $validStones, true)) {
                $fail("Node '{$nodeId}' must have a valid 'stone' field (black or white).");

                return;
            }

            // Children must be an array
            if (! isset($node['children']) || ! is_array($node['children'])) {
                $fail("Node '{$nodeId}' must have a 'children' array field.");

                return;
            }

            // Parent must be null or string
            if (array_key_exists('parent', $node) && $node['parent'] !== null && ! is_string($node['parent'])) {
                $fail("Node '{$nodeId}' parent must be null or a string.");

                return;
            }

            // Validate parent exists (except for root which can have null parent)
            if (isset($node['parent']) && $node['parent'] !== null) {
                if (! array_key_exists($node['parent'], $nodes)) {
                    $fail("Node '{$nodeId}' references non-existent parent '{$node['parent']}'.");

                    return;
                }
            }

            // Validate all children exist
            foreach ($node['children'] as $childId) {
                if (! is_string($childId)) {
                    $fail("Node '{$nodeId}' has invalid child reference (must be string).");

                    return;
                }
                if (! array_key_exists($childId, $nodes)) {
                    $fail("Node '{$nodeId}' references non-existent child '{$childId}'.");

                    return;
                }
            }

            // Validate coordinate structure if present
            if (isset($node['coordinate']) && $node['coordinate'] !== null) {
                if (! is_array($node['coordinate'])) {
                    $fail("Node '{$nodeId}' coordinate must be an array or null.");

                    return;
                }
                if (! isset($node['coordinate']['x']) || ! is_int($node['coordinate']['x']) ||
                    ! isset($node['coordinate']['y']) || ! is_int($node['coordinate']['y'])) {
                    $fail("Node '{$nodeId}' coordinate must have integer x and y fields.");

                    return;
                }
                // Basic bounds check (0-18 covers all board sizes)
                if ($node['coordinate']['x'] < 0 || $node['coordinate']['x'] > 18 ||
                    $node['coordinate']['y'] < 0 || $node['coordinate']['y'] > 18) {
                    $fail("Node '{$nodeId}' coordinate is out of bounds.");

                    return;
                }
            }
        }

        // Validate root node has null parent
        $rootNode = $nodes[$rootId];
        if (isset($rootNode['parent']) && $rootNode['parent'] !== null) {
            $fail('The root node must have a null parent.');

            return;
        }
    }
}
