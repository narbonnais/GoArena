<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractGame extends Model
{
    /**
     * Get the game type identifier (bot or human).
     */
    abstract public function getGameType(): string;

    /**
     * Get the game status (pending, active, finished, abandoned).
     */
    abstract public function getStatus(): string;

    /**
     * Get the user's color in this game.
     */
    abstract public function getUserColor(User $user): ?string;

    /**
     * Get the opponent display name for a user.
     */
    abstract public function getOpponentName(User $user): string;

    /**
     * Get the resume URL for a user, if applicable.
     */
    abstract public function getResumeUrl(User $user): ?string;

    /**
     * Get the detail URL for a user.
     */
    abstract public function getDetailUrl(User $user): string;

    /**
     * Get scores with komi applied, if available.
     *
     * @return array{black: float, white: float}|null
     */
    abstract public function getScores(): ?array;

    /**
     * Determine whether the user can delete the game.
     */
    abstract public function canDelete(User $user): bool;

    /**
     * Get the delete URL for a user, if allowed.
     */
    abstract public function getDeleteUrl(User $user): ?string;

    /**
     * Get the score margin (absolute) from the recorded scores.
     */
    public function getScoreMargin(): ?float
    {
        $scores = $this->getScores();
        if (! $scores || ! isset($scores['black'], $scores['white'])) {
            return null;
        }

        return round(abs($scores['black'] - $scores['white']), 1);
    }

    /**
     * Determine whether the user won the game.
     */
    public function userWonFor(User $user): bool
    {
        $winner = $this->winner;
        if (! $winner || $winner === 'draw') {
            return false;
        }

        $userColor = $this->getUserColor($user);
        if ($userColor === null) {
            return false;
        }

        return $winner === $userColor;
    }

    /**
     * Get a win/loss/draw result string for the user.
     */
    public function getResultFor(User $user): string
    {
        $winner = $this->winner;
        if (! $winner || $winner === 'draw') {
            return 'draw';
        }

        return $this->userWonFor($user) ? 'win' : 'loss';
    }

    /**
     * Build a unified history item for the user.
     *
     * @return array<string, mixed>
     */
    public function toHistoryItemFor(User $user): array
    {
        return [
            'id' => $this->id,
            'game_type' => $this->getGameType(),
            'board_size' => (int) $this->board_size,
            'move_count' => (int) $this->move_count,
            'winner' => $this->winner,
            'end_reason' => $this->end_reason,
            'score_margin' => $this->getScoreMargin(),
            'user_won' => $this->userWonFor($user),
            'created_at' => $this->created_at?->toISOString(),
            'opponent' => $this->getOpponentName($user),
            'detail_url' => $this->getDetailUrl($user),
            'can_delete' => $this->canDelete($user),
            'delete_url' => $this->canDelete($user) ? $this->getDeleteUrl($user) : null,
        ];
    }

    /**
     * Build a unified ongoing item for the user.
     *
     * @return array<string, mixed>
     */
    public function toOngoingItemFor(User $user): array
    {
        return [
            'id' => $this->id,
            'game_type' => $this->getGameType(),
            'board_size' => (int) $this->board_size,
            'move_count' => (int) $this->move_count,
            'updated_at' => $this->updated_at?->toISOString(),
            'status' => $this->getStatus(),
            'status_label' => $this->getStatusLabel(),
            'opponent' => $this->getOpponentName($user),
            'resume_url' => $this->getResumeUrl($user),
            'can_delete' => $this->canDelete($user),
            'delete_url' => $this->canDelete($user) ? $this->getDeleteUrl($user) : null,
        ];
    }

    /**
     * Get a human-friendly status label.
     */
    protected function getStatusLabel(): string
    {
        return match ($this->getStatus()) {
            'pending' => 'Waiting for opponent',
            'active' => 'In progress',
            'abandoned' => 'Abandoned',
            'finished' => 'Finished',
            default => 'In progress',
        };
    }
}
