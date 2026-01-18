<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\ApiResponses;
use App\Rules\ValidGoBoard;
use App\Services\KataGoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoAnalysisController extends Controller
{
    use ApiResponses;

    /**
     * Analyze the current position
     */
    public function analyze(Request $request): JsonResponse
    {
        $limits = config('katago.analysis.limits');

        $validated = $request->validate([
            'board' => ['required', 'array', new ValidGoBoard('size')],
            'size' => 'required|integer|in:9,13,19',
            'currentPlayer' => 'required|string|in:black,white',
            'settings' => 'sometimes|array',
            'settings.maxVisits' => 'sometimes|integer|min:'.$limits['maxVisits']['min'].'|max:'.$limits['maxVisits']['max'],
            'settings.numSearchThreads' => 'sometimes|integer|min:'.$limits['numSearchThreads']['min'].'|max:'.$limits['numSearchThreads']['max'],
            'settings.multiPV' => 'sometimes|integer|min:'.$limits['multiPV']['min'].'|max:'.$limits['multiPV']['max'],
        ]);

        // Build settings with defaults
        $settings = [
            'maxVisits' => $validated['settings']['maxVisits'] ?? $limits['maxVisits']['default'],
            'numSearchThreads' => $validated['settings']['numSearchThreads'] ?? $limits['numSearchThreads']['default'],
            'multiPV' => $validated['settings']['multiPV'] ?? $limits['multiPV']['default'],
        ];

        try {
            $kataGo = new KataGoService;
            $analysis = $kataGo->analyze(
                $validated['board'],
                $validated['currentPlayer'],
                $validated['size'],
                $settings
            );

            // Log for debugging
            \Log::debug('KataGo analysis result', $analysis);

            return response()->json($analysis);
        } catch (\Exception $e) {
            \Log::error('KataGo analysis failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'Analysis temporarily unavailable',
                'code' => 'ANALYSIS_UNAVAILABLE',
                'winRate' => [
                    'black' => 50.0,
                    'white' => 50.0,
                ],
                'scoreEstimate' => [
                    'lead' => 0.0,
                    'winner' => 'black',
                ],
                'topMoves' => [],
            ], 500);
        } finally {
            if (isset($kataGo)) {
                $kataGo->stop();
            }
        }
    }

    /**
     * Get the best move without level restriction
     */
    public function getBestMove(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'board' => ['required', 'array', new ValidGoBoard('size')],
            'size' => 'required|integer|in:9,13,19',
            'currentPlayer' => 'required|string|in:black,white',
        ]);

        $kataGo = new KataGoService;
        try {
            $move = $kataGo->getBestMove(
                $validated['board'],
                $validated['currentPlayer'],
                $validated['size']
            );

            return response()->json([
                'move' => $move,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get best move', ['error' => $e->getMessage()]);

            return $this->serverErrorResponse('Unable to calculate best move');
        } finally {
            $kataGo->stop();
        }
    }
}
