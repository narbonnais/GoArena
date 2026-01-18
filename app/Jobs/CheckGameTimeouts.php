<?php

namespace App\Jobs;

use App\Models\MultiplayerGame;
use App\Services\GameClockService;
use App\Services\MultiplayerGameService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckGameTimeouts implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(GameClockService $clockService, MultiplayerGameService $gameService): void
    {
        // Get all active games
        $games = MultiplayerGame::active()->get();

        foreach ($games as $game) {
            $timedOutPlayer = $clockService->checkTimeout($game);

            if ($timedOutPlayer) {
                $gameService->endGameByTimeout($game, $timedOutPlayer);
            }
        }
    }
}
