<?php

use App\Http\Controllers\AnalysisStudyController;
use App\Http\Controllers\GoGameController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\MultiplayerGameController;
use App\Http\Controllers\RatingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Go game routes
Route::get('/go', [GoGameController::class, 'home'])->name('go.home');

// Learn routes
Route::get('/go/learn', [LessonController::class, 'index'])->name('go.learn');
Route::get('/go/learn/{lesson:slug}', [LessonController::class, 'show'])->name('go.lesson');
Route::post('/go/learn/{lesson:slug}/progress', [LessonController::class, 'updateProgress'])->middleware('auth')->name('go.lesson.progress');
Route::post('/go/learn/{lesson:slug}/complete', [LessonController::class, 'complete'])->middleware('auth')->name('go.lesson.complete');

Route::get('/go/play', function () {
    // Validate query parameters with safe defaults
    $validated = request()->validate([
        'boardSize' => 'nullable|integer|in:9,13,19',
        'botId' => 'nullable|string|max:50',
        'timeControl' => 'nullable|integer|min:0|max:3600',
    ]);

    return Inertia::render('go/Play', [
        'boardSize' => (int) ($validated['boardSize'] ?? 9),
        'botId' => $validated['botId'] ?? null,
        'timeControl' => isset($validated['timeControl']) ? (int) $validated['timeControl'] : null,
    ]);
})->name('go.play');

// Analysis page (public for new analysis, authenticated for studies)
Route::get('/go/analyze', function () {
    // Validate query parameters with safe defaults
    $validated = request()->validate([
        'boardSize' => 'nullable|integer|in:9,13,19',
    ]);

    return Inertia::render('go/Analysis', [
        'boardSize' => (int) ($validated['boardSize'] ?? 19),
    ]);
})->name('go.analyze');

// Go game history routes (authenticated)
Route::middleware(['auth'])->group(function () {
    Route::get('/go/history', [GoGameController::class, 'index'])->name('go.history');
    Route::get('/go/history/{goGame}', [GoGameController::class, 'show'])->name('go.replay');
    Route::post('/go/games', [GoGameController::class, 'store'])->name('go.games.store');
    Route::patch('/go/games/{goGame}', [GoGameController::class, 'update'])->name('go.games.update');
    Route::delete('/go/games/{goGame}', [GoGameController::class, 'destroy'])->name('go.games.destroy');
    Route::get('/go/play/{goGame}', [GoGameController::class, 'resume'])->name('go.play.resume');

    // Analysis studies routes
    Route::get('/go/studies', [AnalysisStudyController::class, 'index'])->name('go.studies');
    Route::get('/go/studies/{study}', [AnalysisStudyController::class, 'show'])->name('go.study.show');

    // Multiplayer routes
    Route::get('/go/multiplayer', [MultiplayerGameController::class, 'lobby'])->name('go.multiplayer');
    Route::get('/multiplayer/{game}', [MultiplayerGameController::class, 'show'])->name('multiplayer.show');
});

// Public multiplayer spectate route
Route::get('/multiplayer/{game}/watch', [MultiplayerGameController::class, 'spectate'])->name('multiplayer.spectate');

// Leaderboard route (public)
Route::get('/leaderboard', [RatingController::class, 'leaderboard'])->name('leaderboard');

require __DIR__.'/settings.php';
