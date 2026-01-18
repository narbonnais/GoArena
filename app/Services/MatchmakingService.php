<?php

namespace App\Services;

use App\Events\MatchFoundEvent;
use App\Models\MatchmakingQueue;
use App\Models\MultiplayerGame;
use App\Models\TimeControl;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MatchmakingService
{
    /**
     * Default queue expiration time in minutes.
     */
    private const QUEUE_EXPIRATION_MINUTES = 15;

    /**
     * Grace period for expanding rating range (in seconds).
     */
    private const EXPAND_RANGE_AFTER_SECONDS = 30;

    /**
     * Amount to expand rating range each time.
     */
    private const EXPAND_RANGE_AMOUNT = 50;

    /**
     * Maximum rating range allowed.
     */
    private const MAX_RATING_DIFF = 500;

    /**
     * Join the matchmaking queue.
     */
    public function joinQueue(
        User $user,
        int $boardSize,
        TimeControl $timeControl,
        bool $isRanked = true,
        int $maxRatingDiff = 200
    ): MatchmakingQueue {
        // Remove any existing entry
        $this->leaveQueue($user);

        // Get or create rating for this board size
        $rating = $user->getOrCreateRating($boardSize);

        // Create queue entry
        return MatchmakingQueue::create([
            'user_id' => $user->id,
            'board_size' => $boardSize,
            'time_control_id' => $timeControl->id,
            'rating' => $rating->rating,
            'max_rating_diff' => min($maxRatingDiff, self::MAX_RATING_DIFF),
            'is_ranked' => $isRanked,
            'joined_at' => now(),
            'expires_at' => now()->addMinutes(self::QUEUE_EXPIRATION_MINUTES),
        ]);
    }

    /**
     * Leave the matchmaking queue.
     */
    public function leaveQueue(User $user): bool
    {
        return MatchmakingQueue::where('user_id', $user->id)->delete() > 0;
    }

    /**
     * Get the user's current queue status.
     */
    public function getQueueStatus(User $user): ?array
    {
        $entry = MatchmakingQueue::with('timeControl')
            ->where('user_id', $user->id)
            ->first();

        if (! $entry) {
            return null;
        }

        return [
            'in_queue' => true,
            'board_size' => $entry->board_size,
            'time_control' => $entry->timeControl,
            'rating' => $entry->rating,
            'max_rating_diff' => $entry->max_rating_diff,
            'is_ranked' => $entry->is_ranked,
            'wait_time_seconds' => $entry->wait_time_seconds,
            'joined_at' => $entry->joined_at->toIso8601String(),
            'expires_at' => $entry->expires_at->toIso8601String(),
        ];
    }

    /**
     * Find a match for a given queue entry.
     */
    public function findMatch(MatchmakingQueue $entry): ?MatchmakingQueue
    {
        return MatchmakingQueue::active()
            ->matchCriteria($entry->board_size, $entry->time_control_id, $entry->is_ranked)
            ->where('user_id', '!=', $entry->user_id)
            ->orderBy('joined_at', 'asc')
            ->get()
            ->first(fn ($candidate) => $entry->canMatchWith($candidate));
    }

    /**
     * Process matchmaking for all entries in the queue.
     *
     * @return array<int, MultiplayerGame> Games created
     */
    public function processQueue(): array
    {
        $games = [];

        // Get all active entries, ordered by wait time
        $entries = MatchmakingQueue::active()
            ->orderBy('joined_at', 'asc')
            ->get();

        $matched = [];

        foreach ($entries as $entry) {
            // Skip if already matched in this batch
            if (in_array($entry->user_id, $matched)) {
                continue;
            }

            $opponent = $this->findMatch($entry);

            if ($opponent && ! in_array($opponent->user_id, $matched)) {
                // Create the game
                $game = $this->createGameFromMatch($entry, $opponent);

                if ($game) {
                    $games[] = $game;
                    $matched[] = $entry->user_id;
                    $matched[] = $opponent->user_id;

                    // Remove both from queue
                    $entry->delete();
                    $opponent->delete();

                    // Broadcast match found event to both players
                    $this->broadcastMatchFound($game, $entry, $opponent);
                }
            }
        }

        return $games;
    }

    /**
     * Create a multiplayer game from two matched queue entries.
     */
    private function createGameFromMatch(MatchmakingQueue $entry1, MatchmakingQueue $entry2): ?MultiplayerGame
    {
        return DB::transaction(function () use ($entry1, $entry2) {
            // Randomly assign colors
            $isEntry1Black = (bool) random_int(0, 1);

            $blackEntry = $isEntry1Black ? $entry1 : $entry2;
            $whiteEntry = $isEntry1Black ? $entry2 : $entry1;

            $timeControl = TimeControl::find($entry1->time_control_id);
            $initialTimeMs = $timeControl->initial_time_seconds * 1000;

            return MultiplayerGame::create([
                'black_player_id' => $blackEntry->user_id,
                'white_player_id' => $whiteEntry->user_id,
                'board_size' => $entry1->board_size,
                'time_control_id' => $entry1->time_control_id,
                'komi' => 6.5, // Standard komi
                'is_ranked' => $entry1->is_ranked,
                'status' => MultiplayerGame::STATUS_PENDING,
                'score_phase' => MultiplayerGame::SCORE_PHASE_NONE,
                'current_player' => 'black',
                'black_time_remaining_ms' => $initialTimeMs,
                'white_time_remaining_ms' => $initialTimeMs,
                'move_history' => [],
                'captures' => ['black' => 0, 'white' => 0],
                'dead_stones' => [],
                'score_acceptance' => ['black' => false, 'white' => false],
            ]);
        });
    }

    /**
     * Broadcast match found events to both players.
     */
    private function broadcastMatchFound(MultiplayerGame $game, MatchmakingQueue $entry1, MatchmakingQueue $entry2): void
    {
        // Load relationships
        $game->load(['blackPlayer', 'whitePlayer', 'timeControl']);

        // Notify both players
        foreach ([$entry1, $entry2] as $entry) {
            $color = $game->black_player_id === $entry->user_id ? 'black' : 'white';
            $opponent = $color === 'black' ? $game->whitePlayer : $game->blackPlayer;

            broadcast(new MatchFoundEvent(
                userId: $entry->user_id,
                gameId: $game->id,
                opponent: [
                    'id' => $opponent->id,
                    'name' => $opponent->display_name_or_name,
                    'rating' => $color === 'black' ? $game->white_rating_before : $game->black_rating_before,
                ],
                yourColor: $color
            ));
        }
    }

    /**
     * Expand rating range for entries waiting too long.
     */
    public function expandRatingRanges(): int
    {
        $threshold = now()->subSeconds(self::EXPAND_RANGE_AFTER_SECONDS);

        return MatchmakingQueue::active()
            ->where('joined_at', '<', $threshold)
            ->where('max_rating_diff', '<', self::MAX_RATING_DIFF)
            ->increment('max_rating_diff', self::EXPAND_RANGE_AMOUNT);
    }

    /**
     * Clean up expired entries.
     */
    public function cleanupExpired(): int
    {
        return MatchmakingQueue::where('expires_at', '<', now())->delete();
    }
}
