<?php

use App\Http\Controllers\AnalysisStudyController;
use App\Http\Controllers\GoAIController;
use App\Http\Controllers\GoAnalysisController;
use App\Http\Controllers\MatchmakingController;
use App\Http\Controllers\MultiplayerGameController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
|
*/

// Rate limit computationally expensive AI endpoints
Route::middleware(['throttle:30,1'])->group(function () {
    Route::post('/go/ai-move', [GoAIController::class, 'getMove']);
    Route::post('/go/analyze', [GoAnalysisController::class, 'analyze']);
    Route::post('/go/best-move', [GoAnalysisController::class, 'getBestMove']);
});

// Analysis studies API routes (authenticated via Sanctum with rate limiting)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->group(function () {
    Route::post('/go/studies', [AnalysisStudyController::class, 'store']);
    Route::patch('/go/studies/{study}', [AnalysisStudyController::class, 'update']);
    Route::delete('/go/studies/{study}', [AnalysisStudyController::class, 'destroy']);
    Route::post('/go/studies/from-game/{goGame}', [AnalysisStudyController::class, 'createFromGame']);
});

// Matchmaking routes (authenticated)
Route::middleware(['auth:sanctum', 'throttle:60,1'])->prefix('matchmaking')->group(function () {
    Route::post('/join', [MatchmakingController::class, 'join']);
    Route::post('/leave', [MatchmakingController::class, 'leave']);
    Route::get('/status', [MatchmakingController::class, 'status']);
    Route::get('/time-controls', [MatchmakingController::class, 'timeControls']);
});

// Multiplayer game routes (authenticated)
Route::middleware(['auth:sanctum', 'throttle:120,1'])->prefix('multiplayer')->group(function () {
    Route::get('/live', [MultiplayerGameController::class, 'liveGames']);
    Route::post('/{game}/move', [MultiplayerGameController::class, 'move']);
    Route::post('/{game}/pass', [MultiplayerGameController::class, 'pass']);
    Route::post('/{game}/resign', [MultiplayerGameController::class, 'resign']);
    Route::post('/{game}/dead-stones/toggle', [MultiplayerGameController::class, 'toggleDeadStone']);
    Route::post('/{game}/accept-score', [MultiplayerGameController::class, 'acceptScore']);
    Route::get('/{game}/state', [MultiplayerGameController::class, 'state']);
});
