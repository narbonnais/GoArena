<?php

namespace App\Services;

use App\Models\MultiplayerGame;
use App\Models\UserRating;

class EloService
{
    /**
     * K-factor configuration (EGF-style).
     */
    private const NEW_PLAYER_GAMES = 30;

    private const NEW_PLAYER_BOOST = 1.5;

    private const K_MIN = 10;

    private const K_MAX = 120;

    /**
     * Calculate ELO changes for both players after a game.
     *
     * @return array{black_change: int, white_change: int}
     */
    public function calculateRatingChanges(
        int $blackRating,
        int $whiteRating,
        string $winner,
        int $blackGamesPlayed,
        int $whiteGamesPlayed
    ): array {
        // Calculate expected scores
        $expectedBlack = $this->calculateExpectedScore($blackRating, $whiteRating);
        $expectedWhite = 1 - $expectedBlack;

        // Actual scores based on result
        $actualBlack = match ($winner) {
            'black' => 1.0,
            'white' => 0.0,
            'draw' => 0.5,
        };
        $actualWhite = 1 - $actualBlack;

        // Get K-factors for each player
        $kBlack = $this->getKFactor($blackRating, $blackGamesPlayed);
        $kWhite = $this->getKFactor($whiteRating, $whiteGamesPlayed);

        // Calculate rating changes
        $blackChange = (int) round($kBlack * ($actualBlack - $expectedBlack));
        $whiteChange = (int) round($kWhite * ($actualWhite - $expectedWhite));

        return [
            'black_change' => $blackChange,
            'white_change' => $whiteChange,
        ];
    }

    /**
     * Apply rating changes after a game.
     */
    public function applyRatingChanges(MultiplayerGame $game): void
    {
        if (! $game->is_ranked || ! $game->winner) {
            return;
        }

        $blackRating = UserRating::where('user_id', $game->black_player_id)
            ->where('board_size', $game->board_size)
            ->first();

        $whiteRating = UserRating::where('user_id', $game->white_player_id)
            ->where('board_size', $game->board_size)
            ->first();

        if (! $blackRating || ! $whiteRating) {
            return;
        }

        $changes = $this->calculateRatingChanges(
            $blackRating->rating,
            $whiteRating->rating,
            $game->winner,
            $blackRating->games_played,
            $whiteRating->games_played
        );

        // Store rating before/after on the game
        $game->update([
            'black_rating_before' => $blackRating->rating,
            'white_rating_before' => $whiteRating->rating,
            'black_rating_after' => max(100, $blackRating->rating + $changes['black_change']),
            'white_rating_after' => max(100, $whiteRating->rating + $changes['white_change']),
        ]);

        $blackResult = $game->winner === 'black'
            ? 'win'
            : ($game->winner === 'white' ? 'loss' : 'draw');
        $whiteResult = $game->winner === 'white'
            ? 'win'
            : ($game->winner === 'black' ? 'loss' : 'draw');

        // Update black player's rating
        $this->updatePlayerRating($blackRating, $changes['black_change'], $blackResult);

        // Update white player's rating
        $this->updatePlayerRating($whiteRating, $changes['white_change'], $whiteResult);
    }

    /**
     * Update a player's rating record.
     */
    private function updatePlayerRating(UserRating $rating, int $change, string $result): void
    {
        $newRating = max(100, $rating->rating + $change);

        $rating->update([
            'rating' => $newRating,
            'games_played' => $rating->games_played + 1,
            'wins' => $rating->wins + ($result === 'win' ? 1 : 0),
            'losses' => $rating->losses + ($result === 'loss' ? 1 : 0),
            'draws' => $rating->draws + ($result === 'draw' ? 1 : 0),
            'peak_rating' => max($rating->peak_rating, $newRating),
        ]);
    }

    /**
     * Calculate expected score using ELO formula.
     * Returns the expected probability of player A winning against player B.
     */
    public function calculateExpectedScore(int $ratingA, int $ratingB): float
    {
        return 1 / (1 + pow(10, ($ratingB - $ratingA) / 400));
    }

    /**
     * Get the K-factor for a player using EGF-style formula.
     * Higher K for lower ratings (faster adjustment), lower K for stronger players (stability).
     *
     * Expected K values:
     * - 700 rating (new player, <30 games): K ≈ 100-120
     * - 700 rating (established): K ≈ 70
     * - 1500 rating (6k): K ≈ 45
     * - 2100 rating (1d): K ≈ 25
     * - 2700+ rating: K ≈ 10
     */
    public function getKFactor(int $rating, int $gamesPlayed): float
    {
        // New players: boost K for faster calibration
        $newPlayerBoost = $gamesPlayed < self::NEW_PLAYER_GAMES ? self::NEW_PLAYER_BOOST : 1.0;

        // EGF formula: con = ((3300 - r) / 200) ^ 1.6
        $con = pow((3300 - $rating) / 200, 1.6);

        // Clamp to reasonable bounds and apply new player boost
        return max(self::K_MIN, min(self::K_MAX, $con * $newPlayerBoost));
    }

    /**
     * Estimate rating change preview (for UI display).
     */
    public function estimateRatingChange(int $playerRating, int $opponentRating, int $gamesPlayed): array
    {
        $expectedScore = $this->calculateExpectedScore($playerRating, $opponentRating);
        $kFactor = $this->getKFactor($playerRating, $gamesPlayed);

        return [
            'win' => (int) round($kFactor * (1 - $expectedScore)),
            'loss' => (int) round($kFactor * (0 - $expectedScore)),
            'draw' => (int) round($kFactor * (0.5 - $expectedScore)),
        ];
    }
}
