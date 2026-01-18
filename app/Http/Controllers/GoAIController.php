<?php

namespace App\Http\Controllers;

use App\Rules\ValidGoBoard;
use App\Services\GoFallbackAiService;
use App\Services\KataGoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoAIController extends Controller
{
    /**
     * Get an AI move for the given game state
     */
    public function getMove(Request $request, GoFallbackAiService $fallbackAi): JsonResponse
    {
        // First validate boardSize to use for coordinate bounds
        $boardSize = $request->validate([
            'boardSize' => 'required|in:9,13,19',
        ])['boardSize'];

        $maxCoord = $boardSize - 1;

        $validated = $request->validate([
            'board' => ['required', 'array', new ValidGoBoard('boardSize')],
            'currentPlayer' => 'required|in:black,white',
            'boardSize' => 'required|in:9,13,19',
            'moveHistory' => 'nullable|array',
            'koPoint' => 'nullable|array',
            'koPoint.x' => "required_with:koPoint|integer|min:0|max:{$maxCoord}",
            'koPoint.y' => "required_with:koPoint|integer|min:0|max:{$maxCoord}",
        ]);

        $board = $validated['board'];
        $size = (int) $validated['boardSize'];
        $color = $validated['currentPlayer'];
        $koPoint = $validated['koPoint'] ?? null;

        $startTime = microtime(true);
        Log::info("[KataGo] Request: {$color} to play on {$size}x{$size} board");

        // Check if KataGo is available
        $binary = config('katago.binary');
        $model = config('katago.model');

        if (file_exists($binary) && file_exists($model)) {
            Log::info('[KataGo] Using KataGo engine');
            $katago = new KataGoService;
            try {
                $move = $katago->getMove($board, $color, $size);
                $elapsed = round((microtime(true) - $startTime) * 1000);
                $moveStr = $move ? "({$move['x']}, {$move['y']})" : 'pass';
                Log::info("[KataGo] Move: {$moveStr} in {$elapsed}ms");

                return response()->json([
                    'move' => $move,
                    'engine' => 'katago',
                ]);
            } catch (RuntimeException $e) {
                Log::error("[KataGo] Process error: {$e->getMessage()}, falling back to simple AI");
            } catch (\Exception $e) {
                Log::error("[KataGo] Unexpected error: {$e->getMessage()}", ['exception' => $e]);
            } finally {
                $katago->stop();
            }
        } else {
            Log::debug('[KataGo] KataGo not available, using fallback AI');
        }

        // Fallback: Simple AI that plays valid moves
        $move = $fallbackAi->getMove($board, $size, $color, $koPoint);
        $elapsed = round((microtime(true) - $startTime) * 1000);
        $moveStr = $move ? "({$move['x']}, {$move['y']})" : 'pass';
        Log::info("[KataGo] Fallback move: {$moveStr} in {$elapsed}ms");

        return response()->json([
            'move' => $move,
            'engine' => 'fallback',
            'profile' => null,
        ]);
    }
}
