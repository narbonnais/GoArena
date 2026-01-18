<?php

namespace App\Services;

class GoRulesService
{
    /**
     * Create an empty board of the specified size.
     *
     * @return array<int, array<int, string|null>>
     */
    public function createEmptyBoard(int $size): array
    {
        return array_fill(0, $size, array_fill(0, $size, null));
    }

    /**
     * Replay move history to reconstruct board and ko point.
     *
     * @param  array<int, array<string, mixed>>  $moveHistory
     * @return array{board: array<int, array<int, string|null>>, ko_point: array{x: int, y: int}|null, captures: array{black: int, white: int}}
     */
    public function replayMoves(int $size, array $moveHistory): array
    {
        $board = $this->createEmptyBoard($size);
        $koPoint = null;
        $captures = ['black' => 0, 'white' => 0];

        foreach ($moveHistory as $move) {
            if (! is_array($move)) {
                continue;
            }

            $stone = $move['stone'] ?? null;
            if ($stone !== 'black' && $stone !== 'white') {
                continue;
            }

            if (! array_key_exists('coordinate', $move) || $move['coordinate'] === null) {
                $koPoint = null;

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

            $result = $this->placeStone($board, ['x' => $x, 'y' => $y], $stone, $koPoint);
            if (! $result['valid']) {
                continue;
            }

            $board = $result['board'];
            $koPoint = $result['ko_point'];
            $captures[$stone] += count($result['captures']);
        }

        return [
            'board' => $board,
            'ko_point' => $koPoint,
            'captures' => $captures,
        ];
    }

    /**
     * Attempt to place a stone and resolve captures.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @param  array{x: int, y: int}  $coord
     * @return array{valid: bool, board: array<int, array<int, string|null>>, captures: array<int, array{x: int, y: int}>, ko_point: array{x: int, y: int}|null, error?: string}
     */
    public function placeStone(array $board, array $coord, string $stone, ?array $koPoint = null): array
    {
        $x = (int) $coord['x'];
        $y = (int) $coord['y'];

        if (! $this->isValidCoordinate($board, $x, $y)) {
            return ['valid' => false, 'board' => $board, 'captures' => [], 'ko_point' => $koPoint, 'error' => 'Invalid coordinate'];
        }

        if (! $this->isEmpty($board, $x, $y)) {
            return ['valid' => false, 'board' => $board, 'captures' => [], 'ko_point' => $koPoint, 'error' => 'Position is occupied'];
        }

        if ($koPoint && $this->coordsEqual($coord, $koPoint)) {
            return ['valid' => false, 'board' => $board, 'captures' => [], 'ko_point' => $koPoint, 'error' => 'Ko rule violation'];
        }

        $newBoard = $this->setStone($board, $x, $y, $stone);

        $opponent = $stone === 'black' ? 'white' : 'black';
        $capturedGroups = $this->findCapturedGroups($newBoard, $opponent);
        $captures = [];

        foreach ($capturedGroups as $group) {
            $captures = array_merge($captures, $group['stones']);
        }

        if ($captures) {
            $newBoard = $this->removeStones($newBoard, $captures);
        }

        $selfGroup = $this->findGroup($newBoard, $x, $y);
        if ($selfGroup && count($selfGroup['liberties']) === 0 && ! $captures) {
            return ['valid' => false, 'board' => $board, 'captures' => [], 'ko_point' => $koPoint, 'error' => 'Suicide is not allowed'];
        }

        $koPoint = $this->detectKo($newBoard, $coord, $captures);

        return [
            'valid' => true,
            'board' => $newBoard,
            'captures' => $captures,
            'ko_point' => $koPoint,
        ];
    }

    /**
     * Validate board coordinates.
     *
     * @param  array<int, array<int, string|null>>  $board
     */
    public function isValidCoordinate(array $board, int $x, int $y): bool
    {
        $size = count($board);

        return $x >= 0 && $x < $size && $y >= 0 && $y < $size;
    }

    /**
     * Determine whether a coordinate is empty.
     *
     * @param  array<int, array<int, string|null>>  $board
     */
    public function isEmpty(array $board, int $x, int $y): bool
    {
        return $this->isValidCoordinate($board, $x, $y) && $board[$y][$x] === null;
    }

    /**
     * Remove stones from the board.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @param  array<int, array{x: int, y: int}>  $coords
     * @return array<int, array<int, string|null>>
     */
    public function removeStones(array $board, array $coords): array
    {
        foreach ($coords as $coord) {
            if (! is_array($coord) || ! isset($coord['x'], $coord['y'])) {
                continue;
            }
            $x = (int) $coord['x'];
            $y = (int) $coord['y'];
            if ($this->isValidCoordinate($board, $x, $y)) {
                $board[$y][$x] = null;
            }
        }

        return $board;
    }

    /**
     * Flood fill to find group and liberties.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @return array{stones: array<int, array{x: int, y: int}>, liberties: array<int, array{x: int, y: int}>, color: string}|null
     */
    private function findGroup(array $board, int $x, int $y): ?array
    {
        if (! $this->isValidCoordinate($board, $x, $y)) {
            return null;
        }

        $stone = $board[$y][$x];
        if ($stone === null) {
            return null;
        }

        $visited = [];
        $stones = [];
        $liberties = [];
        $libertiesSet = [];
        $stack = [[$x, $y]];

        while ($stack) {
            [$cx, $cy] = array_pop($stack);
            $key = $this->coordKey($cx, $cy);

            if (isset($visited[$key])) {
                continue;
            }
            $visited[$key] = true;

            if ($board[$cy][$cx] !== $stone) {
                continue;
            }

            $stones[] = ['x' => $cx, 'y' => $cy];

            foreach ($this->getAdjacentCoordinates($board, $cx, $cy) as [$nx, $ny]) {
                $adjStone = $board[$ny][$nx];
                if ($adjStone === $stone) {
                    $stack[] = [$nx, $ny];
                } elseif ($adjStone === null) {
                    $libKey = $this->coordKey($nx, $ny);
                    if (! isset($libertiesSet[$libKey])) {
                        $liberties[] = ['x' => $nx, 'y' => $ny];
                        $libertiesSet[$libKey] = true;
                    }
                }
            }
        }

        return [
            'stones' => $stones,
            'liberties' => $liberties,
            'color' => $stone,
        ];
    }

    /**
     * Get group info for a stone at the given coordinate.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @return array{stones: array<int, array{x: int, y: int}>, liberties: array<int, array{x: int, y: int}>, color: string}|null
     */
    public function getGroup(array $board, int $x, int $y): ?array
    {
        return $this->findGroup($board, $x, $y);
    }

    /**
     * Find all groups with no liberties for a given color.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @return array<int, array{stones: array<int, array{x: int, y: int}>, liberties: array<int, array{x: int, y: int}>, color: string}>
     */
    private function findCapturedGroups(array $board, string $color): array
    {
        $size = count($board);
        $visited = [];
        $captured = [];

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $key = $this->coordKey($x, $y);
                if (isset($visited[$key])) {
                    continue;
                }

                if ($board[$y][$x] !== $color) {
                    continue;
                }

                $group = $this->findGroup($board, $x, $y);
                if (! $group) {
                    continue;
                }

                foreach ($group['stones'] as $stone) {
                    $visited[$this->coordKey($stone['x'], $stone['y'])] = true;
                }

                if (count($group['liberties']) === 0) {
                    $captured[] = $group;
                }
            }
        }

        return $captured;
    }

    /**
     * Determine if a move creates a ko point.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @param  array{x: int, y: int}  $coord
     * @param  array<int, array{x: int, y: int}>  $captures
     * @return array{x: int, y: int}|null
     */
    private function detectKo(array $board, array $coord, array $captures): ?array
    {
        if (count($captures) !== 1) {
            return null;
        }

        $capturedCoord = $captures[0];
        $neighbors = $this->getAdjacentCoordinates($board, $capturedCoord['x'], $capturedCoord['y']);
        $stoneNeighbors = array_filter($neighbors, function (array $neighbor) use ($board) {
            return $board[$neighbor[1]][$neighbor[0]] !== null;
        });

        if (count($stoneNeighbors) !== 1) {
            return null;
        }

        [$nx, $ny] = array_values($stoneNeighbors)[0];
        if (! $this->coordsEqual($coord, ['x' => $nx, 'y' => $ny])) {
            return null;
        }

        $capturingGroup = $this->findGroup($board, $coord['x'], $coord['y']);
        if ($capturingGroup && count($capturingGroup['liberties']) === 1) {
            return ['x' => $capturedCoord['x'], 'y' => $capturedCoord['y']];
        }

        return null;
    }

    /**
     * Get orthogonal adjacent coordinates.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @return array<int, array{0: int, 1: int}>
     */
    public function getAdjacentCoordinates(array $board, int $x, int $y): array
    {
        $candidates = [
            [$x - 1, $y],
            [$x + 1, $y],
            [$x, $y - 1],
            [$x, $y + 1],
        ];

        return array_values(array_filter(
            $candidates,
            fn (array $coord) => $this->isValidCoordinate($board, $coord[0], $coord[1])
        ));
    }

    /**
     * Update a stone on the board.
     *
     * @param  array<int, array<int, string|null>>  $board
     * @return array<int, array<int, string|null>>
     */
    private function setStone(array $board, int $x, int $y, ?string $stone): array
    {
        $board[$y][$x] = $stone;

        return $board;
    }

    private function coordKey(int $x, int $y): string
    {
        return "{$x},{$y}";
    }

    /**
     * @param  array{x: int, y: int}  $a
     * @param  array{x: int, y: int}  $b
     */
    private function coordsEqual(array $a, array $b): bool
    {
        return (int) $a['x'] === (int) $b['x'] && (int) $a['y'] === (int) $b['y'];
    }
}
