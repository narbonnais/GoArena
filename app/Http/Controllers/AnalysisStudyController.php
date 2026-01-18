<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponses;
use App\Models\AnalysisStudy;
use App\Models\Game;
use App\Rules\ValidMoveTree;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalysisStudyController extends Controller
{
    use ApiResponses, AuthorizesRequests;

    /**
     * Display a listing of the user's studies.
     */
    public function index(Request $request): Response
    {
        $studies = $request->user()
            ->analysisStudies()
            ->orderByDesc('updated_at')
            ->paginate(12)
            ->through(fn (AnalysisStudy $study) => [
                'id' => $study->id,
                'title' => $study->title,
                'description' => $study->description,
                'board_size' => $study->board_size,
                'move_count' => $study->move_count,
                'is_public' => $study->is_public,
                'created_at' => $study->created_at->toISOString(),
                'updated_at' => $study->updated_at->toISOString(),
            ]);

        return Inertia::render('go/Studies', [
            'studies' => $studies,
        ]);
    }

    /**
     * Display the specified study for analysis.
     */
    public function show(Request $request, AnalysisStudy $study): Response
    {
        $this->authorize('view', $study);

        // Only include user ownership info if the current user owns the study
        $isOwner = $request->user()?->id === $study->user_id;

        return Inertia::render('go/Analysis', [
            'study' => [
                'id' => $study->id,
                'title' => $study->title,
                'description' => $study->description,
                'board_size' => $study->board_size,
                'komi' => $study->komi,
                'move_tree' => $study->move_tree,
                'source_game_id' => $study->source_game_id,
                'is_public' => $study->is_public,
                'is_owner' => $isOwner,
                'created_at' => $study->created_at->toISOString(),
                'updated_at' => $study->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Store a newly created study.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'board_size' => 'required|integer|in:9,13,19',
            'komi' => 'required|numeric|min:-50|max:50',
            'move_tree' => ['required', 'array', new ValidMoveTree(5000)],
            'source_game_id' => 'nullable|integer|exists:go_games,id',
            'is_public' => 'boolean',
        ]);

        // Verify source game belongs to user if provided
        if (isset($validated['source_game_id'])) {
            $sourceGame = Game::find($validated['source_game_id']);
            if (! $sourceGame || ! $sourceGame->isParticipant($request->user())) {
                return $this->forbiddenResponse('Invalid source game.');
            }
        }

        $study = $request->user()->analysisStudies()->create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'board_size' => $validated['board_size'],
            'komi' => $validated['komi'],
            'move_tree' => $validated['move_tree'],
            'source_game_id' => $validated['source_game_id'] ?? null,
            'is_public' => $validated['is_public'] ?? false,
        ]);

        return $this->successResponse([
            'study' => [
                'id' => $study->id,
                'title' => $study->title,
                'updated_at' => $study->updated_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Update the specified study.
     */
    public function update(Request $request, AnalysisStudy $study): JsonResponse
    {
        $this->authorize('update', $study);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'move_tree' => ['sometimes', 'required', 'array', new ValidMoveTree(5000)],
            'is_public' => 'sometimes|boolean',
        ]);

        $study->update($validated);

        return $this->successResponse([
            'study' => [
                'id' => $study->id,
                'title' => $study->title,
                'updated_at' => $study->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Remove the specified study.
     */
    public function destroy(Request $request, AnalysisStudy $study): JsonResponse
    {
        $this->authorize('delete', $study);

        $study->delete();

        return $this->successResponse();
    }

    /**
     * Create a study from an existing game.
     */
    public function createFromGame(Request $request, Game $goGame): JsonResponse
    {
        // Verify ownership using policy (route model binding handles 404)
        $this->authorize('view', $goGame);

        // Build initial move tree from game history
        $moveTree = $this->buildMoveTreeFromHistory($goGame->validated_move_history);

        $study = $request->user()->analysisStudies()->create([
            'title' => 'Analysis: '.$goGame->board_size.'×'.$goGame->board_size.' game ('.$goGame->created_at->format('Y-m-d').')',
            'description' => null,
            'board_size' => $goGame->board_size,
            'komi' => $goGame->komi,
            'move_tree' => $moveTree,
            'source_game_id' => $goGame->id,
            'is_public' => false,
        ]);

        return $this->successResponse([
            'study' => [
                'id' => $study->id,
                'title' => $study->title,
            ],
        ], 201);
    }

    /**
     * Build a move tree from a linear move history.
     */
    private function buildMoveTreeFromHistory(array $moveHistory): array
    {
        $nodes = [
            'root' => [
                'id' => 'root',
                'coordinate' => null,
                'stone' => 'white',
                'captures' => [],
                'moveNumber' => 0,
                'parent' => null,
                'children' => [],
                'comment' => null,
                'symbols' => [],
            ],
        ];

        $parentId = 'root';

        foreach ($moveHistory as $index => $move) {
            $nodeId = 'node_'.($index + 1);

            $nodes[$nodeId] = [
                'id' => $nodeId,
                'coordinate' => $move['coordinate'] ?? null,
                'stone' => $move['stone'],
                'captures' => $move['captures'] ?? [],
                'moveNumber' => $move['moveNumber'] ?? ($index + 1),
                'parent' => $parentId,
                'children' => [],
                'comment' => null,
                'symbols' => [],
            ];

            // Add as child of parent
            $nodes[$parentId]['children'][] = $nodeId;

            $parentId = $nodeId;
        }

        return [
            'nodes' => $nodes,
            'rootId' => 'root',
            'currentNodeId' => 'root',
        ];
    }
}
