<?php

namespace App\Http\Controllers;

use App\Models\UserRating;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RatingController extends Controller
{
    /**
     * Show the leaderboard page.
     */
    public function leaderboard(Request $request): Response
    {
        $validated = $request->validate([
            'board_size' => 'nullable|integer|in:9,13,19',
        ]);

        $boardSize = $validated['board_size'] ?? 19;

        // Get top 100 players for this board size
        $leaderboard = UserRating::with('user')
            ->forBoardSize($boardSize)
            ->leaderboard()
            ->limit(100)
            ->get()
            ->map(fn ($rating, $index) => [
                'rank' => $index + 1,
                'user' => [
                    'id' => $rating->user->id,
                    'name' => $rating->user->display_name_or_name,
                    'avatar_url' => $rating->user->avatar_url,
                    'country_code' => $rating->user->country_code,
                ],
                'rating' => $rating->rating,
                'rank_title' => $rating->rank_title,
                'games_played' => $rating->games_played,
                'wins' => $rating->wins,
                'losses' => $rating->losses,
                'win_rate' => $rating->win_rate,
                'peak_rating' => $rating->peak_rating,
            ]);

        return Inertia::render('go/Leaderboard', [
            'leaderboard' => $leaderboard,
            'boardSize' => $boardSize,
        ]);
    }
}
