<?php

use App\Services\GoScoringService;

function boardFromDiagram(array $rows): array
{
    $size = count($rows);
    expect($size)->toBeGreaterThan(0);

    $width = strlen($rows[0]);
    expect($width)->toBe($size);

    $board = [];
    foreach ($rows as $row) {
        expect(strlen($row))->toBe($width);

        $cells = str_split($row);
        $boardRow = [];

        foreach ($cells as $cell) {
            $boardRow[] = match ($cell) {
                'B' => 'black',
                'W' => 'white',
                '.' => null,
                '+' => null,
                default => throw new InvalidArgumentException("Unsupported cell: {$cell}"),
            };
        }

        $board[] = $boardRow;
    }

    return $board;
}

dataset('go_scoring_positions', [
    'empty_5x5' => [
        'rows' => [
            '.....',
            '.....',
            '.....',
            '.....',
            '.....',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 6.5],
            'winner' => 'white',
            'territory' => ['black' => 0, 'white' => 0],
            'stones' => ['black' => 0, 'white' => 0],
        ],
    ],
    'full_alternating_5x5' => [
        'rows' => [
            'BWBWB',
            'WBWBW',
            'BWBWB',
            'WBWBW',
            'BWBWB',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 6.5],
            'winner' => 'white',
            'territory' => ['black' => 0, 'white' => 0],
            'stones' => ['black' => 13, 'white' => 12],
        ],
    ],
    'black_eye_inside_white_border_5x5' => [
        'rows' => [
            'WWWWW',
            'WBBBW',
            'WB.BW',
            'WBBBW',
            'WWWWW',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 1.0, 'white' => 6.5],
            'winner' => 'white',
            'territory' => ['black' => 1, 'white' => 0],
            'stones' => ['black' => 8, 'white' => 16],
        ],
    ],
    'white_eye_inside_black_border_5x5' => [
        'rows' => [
            'BBBBB',
            'BWWWB',
            'BW.WB',
            'BWWWB',
            'BBBBB',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 7.5],
            'winner' => 'white',
            'territory' => ['black' => 0, 'white' => 1],
            'stones' => ['black' => 16, 'white' => 8],
        ],
    ],
    'black_two_single_eyes_inside_white_border_7x7' => [
        'rows' => [
            'WWWWWWW',
            'WBBBBBW',
            'WBB.BBW',
            'WBBBBBW',
            'WBB.BBW',
            'WBBBBBW',
            'WWWWWWW',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 2.0, 'white' => 6.5],
            'winner' => 'white',
            'territory' => ['black' => 2, 'white' => 0],
            'stones' => ['black' => 23, 'white' => 24],
        ],
    ],
    'black_four_point_eye_inside_white_border_7x7' => [
        'rows' => [
            'WWWWWWW',
            'WBBBBBW',
            'WBBBBBW',
            'WBB..BW',
            'WBB..BW',
            'WBBBBBW',
            'WWWWWWW',
        ],
        'komi' => 0.0,
        'expected' => [
            'scores' => ['black' => 4.0, 'white' => 0.0],
            'winner' => 'black',
            'territory' => ['black' => 4, 'white' => 0],
            'stones' => ['black' => 21, 'white' => 24],
        ],
    ],
    'neutral_shared_region_5x5' => [
        'rows' => [
            'BBBBB',
            'B...W',
            'B...W',
            'B...W',
            'WWWWW',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 6.5],
            'winner' => 'white',
            'territory' => ['black' => 0, 'white' => 0],
            'stones' => ['black' => 8, 'white' => 8],
        ],
    ],
    'corner_eye_black_4x4' => [
        'rows' => [
            '.BBB',
            'BBBB',
            'BWWW',
            'WWWW',
        ],
        'komi' => 0.0,
        'expected' => [
            'scores' => ['black' => 1.0, 'white' => 0.0],
            'winner' => 'black',
            'territory' => ['black' => 1, 'white' => 0],
            'stones' => ['black' => 8, 'white' => 7],
        ],
    ],
    'black_dominance_full_5x5' => [
        'rows' => [
            'BBBBB',
            'BBBBB',
            'BBBBB',
            'BBBWW',
            'BBBWW',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 6.5],
            'winner' => 'white',
            'territory' => ['black' => 0, 'white' => 0],
            'stones' => ['black' => 21, 'white' => 4],
        ],
    ],
    'draw_equal_stones_4x4' => [
        'rows' => [
            'BWBW',
            'WBWB',
            'BWBW',
            'WBWB',
        ],
        'komi' => 0.0,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 0.0],
            'winner' => 'draw',
            'territory' => ['black' => 0, 'white' => 0],
            'stones' => ['black' => 8, 'white' => 8],
        ],
    ],
    'balanced_eyes_6x6' => [
        'rows' => [
            'BBBBBB',
            'B.BBBB',
            'BBBBBB',
            'WWWWWW',
            'WWWW.W',
            'WWWWWW',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 1.0, 'white' => 7.5],
            'winner' => 'white',
            'territory' => ['black' => 1, 'white' => 1],
            'stones' => ['black' => 17, 'white' => 17],
        ],
    ],
    'white_eye_inside_black_field_6x6' => [
        'rows' => [
            'BBBBBB',
            'BBBBBB',
            'BBWWWB',
            'BBW.WB',
            'BBWWWB',
            'BBBBBB',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 7.5],
            'winner' => 'white',
            'territory' => ['black' => 0, 'white' => 1],
            'stones' => ['black' => 27, 'white' => 8],
        ],
    ],
    'black_eye_inside_white_field_6x6' => [
        'rows' => [
            'WWWWWW',
            'WWWWWW',
            'WWBBBW',
            'WWB.BW',
            'WWBBBW',
            'WWWWWW',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 1.0, 'white' => 6.5],
            'winner' => 'white',
            'territory' => ['black' => 1, 'white' => 0],
            'stones' => ['black' => 8, 'white' => 27],
        ],
    ],
    'white_two_single_eyes_inside_black_border_7x7' => [
        'rows' => [
            'BBBBBBB',
            'BWWWWWB',
            'BWW.WWB',
            'BWWWWWB',
            'BWW.WWB',
            'BWWWWWB',
            'BBBBBBB',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 8.5],
            'winner' => 'white',
            'territory' => ['black' => 0, 'white' => 2],
            'stones' => ['black' => 24, 'white' => 23],
        ],
    ],
    'white_four_point_eye_inside_black_border_7x7' => [
        'rows' => [
            'BBBBBBB',
            'BWWWWWB',
            'BWWWWWB',
            'BWW..WB',
            'BWW..WB',
            'BWWWWWB',
            'BBBBBBB',
        ],
        'komi' => 0.0,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 4.0],
            'winner' => 'white',
            'territory' => ['black' => 0, 'white' => 4],
            'stones' => ['black' => 24, 'white' => 21],
        ],
    ],
    'komi_swing_black_territory_5x5' => [
        'rows' => [
            'BBBBB',
            'B...B',
            'BBBBB',
            'WWWWW',
            'WWWWW',
        ],
        'komi' => 6.5,
        'expected' => [
            'scores' => ['black' => 3.0, 'white' => 6.5],
            'winner' => 'white',
            'territory' => ['black' => 3, 'white' => 0],
            'stones' => ['black' => 12, 'white' => 10],
        ],
    ],
    'white_corner_eye_4x4' => [
        'rows' => [
            '.WWW',
            'WWWW',
            'WBBB',
            'BBBB',
        ],
        'komi' => 0.0,
        'expected' => [
            'scores' => ['black' => 0.0, 'white' => 1.0],
            'winner' => 'white',
            'territory' => ['black' => 0, 'white' => 1],
            'stones' => ['black' => 7, 'white' => 8],
        ],
    ],
    'territory_draw_6x6' => [
        'rows' => [
            'BBBBBB',
            'B.BB.B',
            'BBBBBB',
            'WWWWWW',
            'WW.WWW',
            'WWWWWW',
        ],
        'komi' => 0.0,
        'expected' => [
            'scores' => ['black' => 2.0, 'white' => 1.0],
            'winner' => 'black',
            'territory' => ['black' => 2, 'white' => 1],
            'stones' => ['black' => 16, 'white' => 17],
        ],
    ],
]);

it('scores known board positions with Japanese territory scoring and komi', function (array $rows, float $komi, array $expected) {
    $service = new GoScoringService;
    $board = boardFromDiagram($rows);

    $result = $service->calculateScoreFromBoard($board, $komi);

    expect($result['scores']['black'])->toEqual($expected['scores']['black']);
    expect($result['scores']['white'])->toEqual($expected['scores']['white']);
    expect($result['winner'])->toBe($expected['winner']);
    expect($result['territory'])->toBe($expected['territory']);
    expect($result['stones'])->toBe($expected['stones']);
})->with('go_scoring_positions');
