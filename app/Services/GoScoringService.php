<?php

namespace App\Services;

class GoScoringService
{
    /**
     * Calculate final score from move history using Japanese territory scoring.
     *
     * @param  array<int, array<string, mixed>>  $moveHistory
     * @return array{scores: array{black: float, white: float}, winner: string, territory: array{black: int, white: int}, stones: array{black: int, white: int}, captures: array{black: int, white: int}}
     */
    public function calculateScoreFromMoveHistory(int $size, array $moveHistory, float $komi): array
    {
        $board = $this->buildBoardState($size, $moveHistory);
        $captures = $this->countCapturesFromMoveHistory($moveHistory);

        return $this->calculateScoreFromBoard($board, $komi, $captures);
    }

    /**
     * Calculate score from a board state using Japanese territory scoring.
     * Score = Territory + Captures + Komi.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @param  array{black: int, white: int}  $captures
     * @return array{scores: array{black: float, white: float}, winner: string, territory: array{black: int, white: int}, stones: array{black: int, white: int}, captures: array{black: int, white: int}}
     */
    public function calculateScoreFromBoard(array $board, float $komi, array $captures = ['black' => 0, 'white' => 0]): array
    {
        $territory = $this->calculateTerritory($board);
        $stones = $this->countStones($board);
        $captures = array_merge(['black' => 0, 'white' => 0], $captures);

        $blackTotal = $territory['black'] + $captures['black'];
        $whiteTotal = $territory['white'] + $captures['white'] + $komi;

        $winner = $blackTotal > $whiteTotal
            ? 'black'
            : ($whiteTotal > $blackTotal ? 'white' : 'draw');

        return [
            'scores' => [
                'black' => $blackTotal,
                'white' => $whiteTotal,
            ],
            'winner' => $winner,
            'territory' => $territory,
            'stones' => $stones,
            'captures' => $captures,
        ];
    }

    /**
     * Build board state from move history (captures are applied if present).
     *
     * @param  array<int, array<string, mixed>>  $moveHistory
     * @return array<int, array<int, string|null>>
     */
    public function buildBoardState(int $size, array $moveHistory): array
    {
        $board = array_fill(0, $size, array_fill(0, $size, null));

        foreach ($moveHistory as $move) {
            if (! is_array($move)) {
                continue;
            }

            if (isset($move['captures']) && is_array($move['captures'])) {
                foreach ($move['captures'] as $capture) {
                    if (! is_array($capture) || ! isset($capture['x'], $capture['y'])) {
                        continue;
                    }
                    $x = (int) $capture['x'];
                    $y = (int) $capture['y'];
                    if ($this->isValidCoordinate($board, $x, $y)) {
                        $board[$y][$x] = null;
                    }
                }
            }

            if (! array_key_exists('coordinate', $move) || $move['coordinate'] === null) {
                continue;
            }

            if (! is_array($move['coordinate']) || ! isset($move['coordinate']['x'], $move['coordinate']['y'])) {
                continue;
            }

            $x = (int) $move['coordinate']['x'];
            $y = (int) $move['coordinate']['y'];
            if (! $this->isValidCoordinate($board, $x, $y)) {
                continue;
            }

            $stone = $move['stone'] ?? null;
            if ($stone === 'black' || $stone === 'white') {
                $board[$y][$x] = $stone;
            }
        }

        return $board;
    }

    /**
     * Count captures for each color from move history.
     *
     * @param  array<int, array<string, mixed>>  $moveHistory
     * @return array{black: int, white: int}
     */
    public function countCapturesFromMoveHistory(array $moveHistory): array
    {
        $captures = ['black' => 0, 'white' => 0];

        foreach ($moveHistory as $move) {
            if (! is_array($move)) {
                continue;
            }

            $stone = $move['stone'] ?? null;
            if ($stone !== 'black' && $stone !== 'white') {
                continue;
            }

            $moveCaptures = $move['captures'] ?? null;
            if (! is_array($moveCaptures)) {
                continue;
            }

            $captures[$stone] += count($moveCaptures);
        }

        return $captures;
    }

    /**
     * Flood fill an empty region and track bordering stones.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @param  array{0: int, 1: int}  $start
     * @param  array<string, bool>  $visited
     * @return array{points: int, borders: array<string, bool>}
     */
    private function floodFillTerritory(array $board, array $start, array &$visited): array
    {
        $stack = [$start];
        $points = 0;
        $borders = [];

        while ($stack) {
            [$x, $y] = array_pop($stack);
            $key = "{$x},{$y}";
            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            $stone = $board[$y][$x];
            if ($stone === null) {
                $points++;
                foreach ($this->getAdjacentCoordinates($board, $x, $y) as [$nx, $ny]) {
                    $nKey = "{$nx},{$ny}";
                    if (! isset($visited[$nKey])) {
                        $stack[] = [$nx, $ny];
                    }
                }
            } else {
                $borders[$stone] = true;
            }
        }

        return ['points' => $points, 'borders' => $borders];
    }

    /**
     * Calculate territory for both players (empty regions bordered by one color).
     *
     * @param  array<int, array<int, string|null>>  $board
     * @return array{black: int, white: int}
     */
    private function calculateTerritory(array $board): array
    {
        $size = count($board);
        $visited = [];
        $black = 0;
        $white = 0;

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $key = "{$x},{$y}";
                if (isset($visited[$key])) {
                    continue;
                }

                $stone = $board[$y][$x];
                if ($stone !== null) {
                    $visited[$key] = true;

                    continue;
                }

                $region = $this->floodFillTerritory($board, [$x, $y], $visited);
                $borders = array_keys($region['borders']);

                if (count($borders) === 1) {
                    if ($borders[0] === 'black') {
                        $black += $region['points'];
                    } else {
                        $white += $region['points'];
                    }
                }
            }
        }

        return ['black' => $black, 'white' => $white];
    }

    /**
     * Count stones on the board for each color.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @return array{black: int, white: int}
     */
    private function countStones(array $board): array
    {
        $black = 0;
        $white = 0;

        foreach ($board as $row) {
            foreach ($row as $stone) {
                if ($stone === 'black') {
                    $black++;
                } elseif ($stone === 'white') {
                    $white++;
                }
            }
        }

        return ['black' => $black, 'white' => $white];
    }

    /**
     * Get orthogonal adjacent coordinates.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @return array<int, array{0: int, 1: int}>
     */
    private function getAdjacentCoordinates(array $board, int $x, int $y): array
    {
        $size = count($board);
        $candidates = [
            [$x - 1, $y],
            [$x + 1, $y],
            [$x, $y - 1],
            [$x, $y + 1],
        ];

        return array_values(array_filter($candidates, fn ($coord) => $this->isValidCoordinate($board, $coord[0], $coord[1])));
    }

    /**
     * Validate board coordinates.
     *
     * @param  array<int, array<int, string|null>>  $board
     */
    private function isValidCoordinate(array $board, int $x, int $y): bool
    {
        $size = count($board);

        return $x >= 0 && $x < $size && $y >= 0 && $y < $size;
    }
}
