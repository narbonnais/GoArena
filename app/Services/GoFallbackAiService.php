<?php

namespace App\Services;

class GoFallbackAiService
{
    public function __construct(
        private GoRulesService $rulesService
    ) {}

    /**
     * Pick a reasonable move when KataGo is unavailable.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @param  array{x: int, y: int}|null  $koPoint
     * @return array{x: int, y: int}|null
     */
    public function getMove(array $board, int $size, string $color, ?array $koPoint): ?array
    {
        $validMoves = [];
        $captureMoves = [];
        $defenseMoves = [];

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if (($board[$y][$x] ?? null) !== null) {
                    continue;
                }

                if ($koPoint && $koPoint['x'] === $x && $koPoint['y'] === $y) {
                    continue;
                }

                $result = $this->rulesService->placeStone($board, ['x' => $x, 'y' => $y], $color, $koPoint);
                if (! $result['valid']) {
                    continue;
                }

                $move = ['x' => $x, 'y' => $y];
                $validMoves[] = $move;

                if (! empty($result['captures'])) {
                    $captureMoves[] = $move;
                }

                if ($this->wouldSaveAtari($board, $result['board'], $x, $y, $color)) {
                    $defenseMoves[] = $move;
                }
            }
        }

        if ($captureMoves) {
            return $captureMoves[array_rand($captureMoves)];
        }

        if ($defenseMoves) {
            return $defenseMoves[array_rand($defenseMoves)];
        }

        if (! $validMoves) {
            return null;
        }

        $scoredMoves = array_map(function ($move) use ($board, $size) {
            $score = $this->scoreMovePosition($board, $move['x'], $move['y'], $size);

            return ['move' => $move, 'score' => $score];
        }, $validMoves);

        usort($scoredMoves, fn ($a, $b) => $b['score'] <=> $a['score']);

        $topMoves = array_slice($scoredMoves, 0, max(3, (int) ceil(count($scoredMoves) / 4)));

        return $topMoves[array_rand($topMoves)]['move'];
    }

    /**
     * Check if the move rescues adjacent friendly stones in atari.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @param  array<int, array<int, string|null>>  $newBoard
     */
    private function wouldSaveAtari(array $board, array $newBoard, int $x, int $y, string $color): bool
    {
        $neighbors = $this->rulesService->getAdjacentCoordinates($board, $x, $y);
        if (! $neighbors) {
            return false;
        }

        $newGroup = null;

        foreach ($neighbors as [$nx, $ny]) {
            if (($board[$ny][$nx] ?? null) !== $color) {
                continue;
            }

            $group = $this->rulesService->getGroup($board, $nx, $ny);
            if (! $group || count($group['liberties']) !== 1) {
                continue;
            }

            if ($newGroup === null) {
                $newGroup = $this->rulesService->getGroup($newBoard, $x, $y);
            }

            if ($newGroup && count($newGroup['liberties']) > 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Score a move position based on board location and nearby stones.
     *
     * @param  array<int, array<int, string|null>>  $board
     */
    private function scoreMovePosition(array $board, int $x, int $y, int $size): float
    {
        $score = 0.0;

        // Prefer positions closer to center.
        $centerX = ($size - 1) / 2;
        $centerY = ($size - 1) / 2;
        $distToCenter = sqrt(pow($x - $centerX, 2) + pow($y - $centerY, 2));
        $maxDist = sqrt(2) * $centerX;
        $score += (1 - $distToCenter / $maxDist) * 2;

        // Prefer positions near existing stones.
        foreach ($this->rulesService->getAdjacentCoordinates($board, $x, $y) as [$nx, $ny]) {
            if (($board[$ny][$nx] ?? null) !== null) {
                $score += 1;
            }
        }

        // Slight randomness.
        $score += mt_rand(0, 100) / 100;

        return $score;
    }
}
