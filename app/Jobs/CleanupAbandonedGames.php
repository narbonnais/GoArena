<?php

namespace App\Jobs;

use App\Models\GameConnection;
use App\Models\MultiplayerGame;
use App\Services\MultiplayerGameService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupAbandonedGames implements ShouldQueue
{
    use Queueable;

    /**
     * Grace period in seconds before marking a game as abandoned.
     */
    private const ABANDONMENT_THRESHOLD_SECONDS = 120; // 2 minutes

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
    public function handle(MultiplayerGameService $gameService): void
    {
        $threshold = now()->subSeconds(self::ABANDONMENT_THRESHOLD_SECONDS);

        // Get all active games
        $games = MultiplayerGame::active()->get();

        foreach ($games as $game) {
            // Check for disconnected players
            $connections = GameConnection::where('game_id', $game->id)->get();

            foreach (['black_player_id', 'white_player_id'] as $playerField) {
                $playerId = $game->{$playerField};
                $playerColor = $playerField === 'black_player_id' ? 'black' : 'white';

                $connection = $connections->firstWhere('user_id', $playerId);

                // If no connection record or disconnected for too long
                if (! $connection) {
                    continue;
                }

                if ($connection->disconnected_at && $connection->disconnected_at < $threshold) {
                    // Player has been disconnected for too long - mark game as abandoned
                    $gameService->endGameByAbandonment($game, $playerColor);
                    break;
                }
            }
        }
    }
}
