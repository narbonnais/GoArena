<?php

namespace App\Services;

use App\Models\Game;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

class GameHistoryService
{
    /**
     * Get ongoing games (bot + human) for a user.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getOngoingGames(User $user): array
    {
        return Game::query()
            ->forUser($user)
            ->whereIn('status', ['pending', 'active'])
            ->with(['blackPlayer', 'whitePlayer'])
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Game $game) => $game->toOngoingItemFor($user))
            ->all();
    }

    /**
     * Get finished games (bot + human) for a user with pagination.
     */
    public function getFinishedGames(User $user, int $perPage = 10): LengthAwarePaginator
    {
        $paginator = Game::query()
            ->forUser($user)
            ->whereIn('status', ['finished', 'abandoned'])
            ->with(['blackPlayer', 'whitePlayer'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(
                fn (Game $game) => $game->toHistoryItemFor($user)
            )
        );

        return $paginator;
    }

    /**
     * Get recent finished games for the play dashboard.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRecentGames(User $user, int $limit = 3): array
    {
        return Game::query()
            ->forUser($user)
            ->whereIn('status', ['finished', 'abandoned'])
            ->with(['blackPlayer', 'whitePlayer'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->map(fn (Game $game) => [
                'id' => $game->id,
                'opponent' => $game->getOpponentName($user),
                'result' => $game->getResultFor($user),
                'boardSize' => (int) $game->board_size,
                'date' => $game->created_at?->diffForHumans() ?? '',
            ])
            ->all();
    }
}
