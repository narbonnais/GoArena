<?php

namespace Database\Seeders;

use App\Models\Lesson;
use Illuminate\Database\Seeder;

class LessonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lessons = [
            $this->createIntroductionLesson(),
            $this->createCapturingLesson(),
            $this->createTerritoryLesson(),
            $this->createKoRuleLesson(),
            $this->createBasicStrategyLesson(),
        ];

        foreach ($lessons as $lesson) {
            Lesson::updateOrCreate(
                ['slug' => $lesson['slug']],
                $lesson
            );
        }
    }

    private function createIntroductionLesson(): array
    {
        return [
            'slug' => 'introduction-to-go',
            'title' => 'Introduction to Go',
            'description' => 'Learn the basic rules of Go, including how to place stones and the objective of the game.',
            'category' => 'basics',
            'difficulty' => 'beginner',
            'duration' => '10 min',
            'order' => 1,
            'prerequisites' => [],
            'steps' => [
                [
                    'id' => 'intro-1',
                    'type' => 'instruction',
                    'title' => 'Welcome to Go',
                    'instruction' => 'Go is one of the oldest board games in the world, originating in China over 4,000 years ago. The goal is simple: surround more territory than your opponent while capturing their stones.',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                ],
                [
                    'id' => 'intro-2',
                    'type' => 'instruction',
                    'title' => 'The Board',
                    'instruction' => 'Go is played on a grid of lines. Beginners typically start on a 9x9 board, but the standard size is 19x19. Stones are placed on the intersections of the lines, not in the squares.',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 4, 'y' => 4]], 'type' => 'circle', 'color' => '#22c55e'],
                    ],
                ],
                [
                    'id' => 'intro-3',
                    'type' => 'placement',
                    'title' => 'Place Your First Stone',
                    'instruction' => 'Black always plays first. Try placing a stone on any intersection on the board.',
                    'hint' => 'Click anywhere on the board to place a black stone.',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                    'correctMoves' => [
                        ['x' => 0, 'y' => 0], ['x' => 1, 'y' => 0], ['x' => 2, 'y' => 0], ['x' => 3, 'y' => 0], ['x' => 4, 'y' => 0], ['x' => 5, 'y' => 0], ['x' => 6, 'y' => 0], ['x' => 7, 'y' => 0], ['x' => 8, 'y' => 0],
                        ['x' => 0, 'y' => 1], ['x' => 1, 'y' => 1], ['x' => 2, 'y' => 1], ['x' => 3, 'y' => 1], ['x' => 4, 'y' => 1], ['x' => 5, 'y' => 1], ['x' => 6, 'y' => 1], ['x' => 7, 'y' => 1], ['x' => 8, 'y' => 1],
                        ['x' => 0, 'y' => 2], ['x' => 1, 'y' => 2], ['x' => 2, 'y' => 2], ['x' => 3, 'y' => 2], ['x' => 4, 'y' => 2], ['x' => 5, 'y' => 2], ['x' => 6, 'y' => 2], ['x' => 7, 'y' => 2], ['x' => 8, 'y' => 2],
                        ['x' => 0, 'y' => 3], ['x' => 1, 'y' => 3], ['x' => 2, 'y' => 3], ['x' => 3, 'y' => 3], ['x' => 4, 'y' => 3], ['x' => 5, 'y' => 3], ['x' => 6, 'y' => 3], ['x' => 7, 'y' => 3], ['x' => 8, 'y' => 3],
                        ['x' => 0, 'y' => 4], ['x' => 1, 'y' => 4], ['x' => 2, 'y' => 4], ['x' => 3, 'y' => 4], ['x' => 4, 'y' => 4], ['x' => 5, 'y' => 4], ['x' => 6, 'y' => 4], ['x' => 7, 'y' => 4], ['x' => 8, 'y' => 4],
                        ['x' => 0, 'y' => 5], ['x' => 1, 'y' => 5], ['x' => 2, 'y' => 5], ['x' => 3, 'y' => 5], ['x' => 4, 'y' => 5], ['x' => 5, 'y' => 5], ['x' => 6, 'y' => 5], ['x' => 7, 'y' => 5], ['x' => 8, 'y' => 5],
                        ['x' => 0, 'y' => 6], ['x' => 1, 'y' => 6], ['x' => 2, 'y' => 6], ['x' => 3, 'y' => 6], ['x' => 4, 'y' => 6], ['x' => 5, 'y' => 6], ['x' => 6, 'y' => 6], ['x' => 7, 'y' => 6], ['x' => 8, 'y' => 6],
                        ['x' => 0, 'y' => 7], ['x' => 1, 'y' => 7], ['x' => 2, 'y' => 7], ['x' => 3, 'y' => 7], ['x' => 4, 'y' => 7], ['x' => 5, 'y' => 7], ['x' => 6, 'y' => 7], ['x' => 7, 'y' => 7], ['x' => 8, 'y' => 7],
                        ['x' => 0, 'y' => 8], ['x' => 1, 'y' => 8], ['x' => 2, 'y' => 8], ['x' => 3, 'y' => 8], ['x' => 4, 'y' => 8], ['x' => 5, 'y' => 8], ['x' => 6, 'y' => 8], ['x' => 7, 'y' => 8], ['x' => 8, 'y' => 8],
                    ],
                ],
                [
                    'id' => 'intro-4',
                    'type' => 'instruction',
                    'title' => 'Taking Turns',
                    'instruction' => 'Players alternate placing stones. Once placed, stones never move, but they can be captured. The game ends when both players pass consecutively.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 2, 'y' => 2], ['x' => 4, 'y' => 4]],
                    'initialWhite' => [['x' => 6, 'y' => 2], ['x' => 6, 'y' => 6]],
                ],
                [
                    'id' => 'intro-5',
                    'type' => 'instruction',
                    'title' => 'Congratulations!',
                    'instruction' => 'You have completed the introduction. In the next lesson, you will learn about capturing stones - one of the most important concepts in Go!',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                ],
            ],
        ];
    }

    private function createCapturingLesson(): array
    {
        return [
            'slug' => 'capturing-stones',
            'title' => 'Capturing Stones',
            'description' => "Master the art of capturing your opponent's stones by surrounding them.",
            'category' => 'capturing',
            'difficulty' => 'beginner',
            'duration' => '15 min',
            'order' => 2,
            'prerequisites' => [1],
            'steps' => [
                [
                    'id' => 'capture-1',
                    'type' => 'instruction',
                    'title' => 'Liberties',
                    'instruction' => 'Every stone needs "liberties" to survive - these are empty adjacent points connected by lines. A stone in the center has 4 liberties. Look at the highlighted stone.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 4, 'y' => 4]],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 4, 'y' => 3], ['x' => 4, 'y' => 5], ['x' => 3, 'y' => 4], ['x' => 5, 'y' => 4]], 'type' => 'circle', 'color' => '#22c55e'],
                    ],
                ],
                [
                    'id' => 'capture-2',
                    'type' => 'instruction',
                    'title' => 'Edge and Corner',
                    'instruction' => 'Stones on the edge have only 3 liberties, and stones in the corner have only 2. This makes them easier to capture!',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 0, 'y' => 0], ['x' => 4, 'y' => 0]],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 1, 'y' => 0], ['x' => 0, 'y' => 1]], 'type' => 'circle', 'color' => '#eab308'],
                        ['coordinates' => [['x' => 3, 'y' => 0], ['x' => 5, 'y' => 0], ['x' => 4, 'y' => 1]], 'type' => 'circle', 'color' => '#22c55e'],
                    ],
                ],
                [
                    'id' => 'capture-3',
                    'type' => 'instruction',
                    'title' => 'Surrounding Stones',
                    'instruction' => 'To capture stones, you must occupy ALL of their liberties. When a stone has no liberties left, it is removed from the board.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 4, 'y' => 4]],
                    'initialWhite' => [['x' => 4, 'y' => 3], ['x' => 3, 'y' => 4], ['x' => 5, 'y' => 4]],
                    'highlights' => [
                        ['coordinates' => [['x' => 4, 'y' => 5]], 'type' => 'square', 'color' => '#ef4444'],
                    ],
                ],
                [
                    'id' => 'capture-4',
                    'type' => 'puzzle',
                    'title' => 'Capture the Stone!',
                    'instruction' => "The black stone has only one liberty left. Play White's move to capture it!",
                    'hint' => 'Look for the last remaining liberty of the black stone.',
                    'wrongMoveResponse' => "That's not the last liberty. Try again!",
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 4, 'y' => 4]],
                    'initialWhite' => [['x' => 4, 'y' => 3], ['x' => 3, 'y' => 4], ['x' => 5, 'y' => 4]],
                    'correctMoves' => [
                        ['x' => 4, 'y' => 5],
                    ],
                    'playerColor' => 'white',
                ],
                [
                    'id' => 'capture-5',
                    'type' => 'instruction',
                    'title' => 'Connected Stones',
                    'instruction' => 'Stones of the same color connected by lines share their liberties. This group of two black stones has 6 liberties total.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 4, 'y' => 4], ['x' => 5, 'y' => 4]],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 3, 'y' => 4], ['x' => 4, 'y' => 3], ['x' => 4, 'y' => 5], ['x' => 5, 'y' => 3], ['x' => 5, 'y' => 5], ['x' => 6, 'y' => 4]], 'type' => 'circle', 'color' => '#22c55e'],
                    ],
                ],
                [
                    'id' => 'capture-6',
                    'type' => 'puzzle',
                    'title' => 'Capture the Stone!',
                    'instruction' => 'This white stone has only one liberty left. Find and play the capturing move!',
                    'hint' => 'Look for the last empty point adjacent to the white stone.',
                    'wrongMoveResponse' => "That's not the last liberty. Find the empty point next to the white stone!",
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 4, 'y' => 2], ['x' => 5, 'y' => 3], ['x' => 4, 'y' => 4]],
                    'initialWhite' => [['x' => 4, 'y' => 3]],
                    'correctMoves' => [
                        ['x' => 3, 'y' => 3],
                    ],
                ],
                [
                    'id' => 'capture-7',
                    'type' => 'instruction',
                    'title' => 'Great Progress!',
                    'instruction' => 'Excellent! You now understand how to capture stones. Remember: surround all liberties to capture. Connected stones are stronger because they share liberties!',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                ],
            ],
        ];
    }

    private function createTerritoryLesson(): array
    {
        return [
            'slug' => 'territory-and-scoring',
            'title' => 'Territory & Scoring',
            'description' => 'Understand how to count territory and calculate the final score.',
            'category' => 'territory',
            'difficulty' => 'beginner',
            'duration' => '12 min',
            'order' => 3,
            'prerequisites' => [2],
            'steps' => [
                [
                    'id' => 'territory-1',
                    'type' => 'instruction',
                    'title' => 'What is Territory?',
                    'instruction' => 'Territory is empty space that you have surrounded with your stones. At the end of the game, you score one point for each intersection of territory you control.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 0, 'y' => 2], ['x' => 1, 'y' => 2], ['x' => 2, 'y' => 2], ['x' => 2, 'y' => 1], ['x' => 2, 'y' => 0]],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 0, 'y' => 0], ['x' => 1, 'y' => 0], ['x' => 0, 'y' => 1], ['x' => 1, 'y' => 1]], 'type' => 'square', 'color' => '#1a1a1a'],
                    ],
                ],
                [
                    'id' => 'territory-2',
                    'type' => 'instruction',
                    'title' => 'Counting Territory',
                    'instruction' => 'Black has surrounded 4 points in the corner (marked). White has surrounded 6 points on the side. At the end, we count: Black = 4 points, White = 6 points.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 0, 'y' => 2], ['x' => 1, 'y' => 2], ['x' => 2, 'y' => 2], ['x' => 2, 'y' => 1], ['x' => 2, 'y' => 0]],
                    'initialWhite' => [['x' => 6, 'y' => 0], ['x' => 6, 'y' => 1], ['x' => 6, 'y' => 2], ['x' => 6, 'y' => 3]],
                    'highlights' => [
                        ['coordinates' => [['x' => 0, 'y' => 0], ['x' => 1, 'y' => 0], ['x' => 0, 'y' => 1], ['x' => 1, 'y' => 1]], 'type' => 'square', 'color' => '#1a1a1a'],
                        ['coordinates' => [['x' => 7, 'y' => 0], ['x' => 8, 'y' => 0], ['x' => 7, 'y' => 1], ['x' => 8, 'y' => 1], ['x' => 7, 'y' => 2], ['x' => 8, 'y' => 2]], 'type' => 'square', 'color' => '#f5f5f5'],
                    ],
                ],
                [
                    'id' => 'territory-3',
                    'type' => 'instruction',
                    'title' => 'Komi (Compensation)',
                    'instruction' => "Since Black plays first, White receives extra points called 'komi' (usually 6.5 points). This makes the game fair. The 0.5 prevents ties.",
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                ],
                [
                    'id' => 'territory-4',
                    'type' => 'instruction',
                    'title' => 'Captures Count Too!',
                    'instruction' => 'Your captured stones also count as points. Each stone you capture is worth 1 point. Final score = Territory + Captures + Komi (for White).',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 2, 'y' => 2], ['x' => 2, 'y' => 3], ['x' => 3, 'y' => 3], ['x' => 3, 'y' => 2]],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 2, 'y' => 2], ['x' => 2, 'y' => 3], ['x' => 3, 'y' => 3], ['x' => 3, 'y' => 2]], 'type' => 'circle', 'color' => '#22c55e'],
                    ],
                ],
                [
                    'id' => 'territory-5',
                    'type' => 'instruction',
                    'title' => 'Game End',
                    'instruction' => 'The game ends when both players pass consecutively. Players pass when they believe there are no more useful moves. Then territories are counted.',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                ],
                [
                    'id' => 'territory-6',
                    'type' => 'instruction',
                    'title' => 'Well Done!',
                    'instruction' => "You understand the basics of scoring! Remember: surround territory, capture stones, and White gets komi. In the next lesson, you'll learn about the Ko rule.",
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                ],
            ],
        ];
    }

    private function createKoRuleLesson(): array
    {
        return [
            'slug' => 'ko-rule',
            'title' => 'Ko Rule & Special Situations',
            'description' => 'Learn about the Ko rule and other special situations that can arise during play.',
            'category' => 'basics',
            'difficulty' => 'beginner',
            'duration' => '15 min',
            'order' => 4,
            'prerequisites' => [3],
            'steps' => [
                [
                    'id' => 'ko-1',
                    'type' => 'instruction',
                    'title' => 'What is Ko?',
                    'instruction' => 'Ko is a situation where players could capture back and forth forever. To prevent infinite loops, the Ko rule exists: you cannot immediately recapture a ko.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 3, 'y' => 3], ['x' => 4, 'y' => 2], ['x' => 5, 'y' => 3], ['x' => 4, 'y' => 4]],
                    'initialWhite' => [['x' => 3, 'y' => 2], ['x' => 5, 'y' => 2], ['x' => 4, 'y' => 1]],
                    'highlights' => [
                        ['coordinates' => [['x' => 4, 'y' => 3]], 'type' => 'square', 'color' => '#ef4444'],
                    ],
                ],
                [
                    'id' => 'ko-2',
                    'type' => 'instruction',
                    'title' => 'Ko Pattern',
                    'instruction' => 'Here, if White captures at the marked point, Black cannot immediately recapture. Black must play elsewhere first (a "ko threat"), then can recapture if White ignores the threat.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 3, 'y' => 3], ['x' => 4, 'y' => 2], ['x' => 5, 'y' => 3], ['x' => 4, 'y' => 4]],
                    'initialWhite' => [['x' => 3, 'y' => 2], ['x' => 5, 'y' => 2], ['x' => 4, 'y' => 1], ['x' => 4, 'y' => 3]],
                    'highlights' => [
                        ['coordinates' => [['x' => 4, 'y' => 2]], 'type' => 'circle', 'color' => '#ef4444'],
                    ],
                ],
                [
                    'id' => 'ko-3',
                    'type' => 'instruction',
                    'title' => 'Suicide is Forbidden',
                    'instruction' => 'You cannot play a move that would immediately cause your own stone to have zero liberties (suicide), unless it captures opponent stones in the process.',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [['x' => 4, 'y' => 3], ['x' => 3, 'y' => 4], ['x' => 5, 'y' => 4], ['x' => 4, 'y' => 5]],
                    'highlights' => [
                        ['coordinates' => [['x' => 4, 'y' => 4]], 'type' => 'square', 'color' => '#ef4444'],
                    ],
                ],
                [
                    'id' => 'ko-4',
                    'type' => 'instruction',
                    'title' => 'Eyes and Life',
                    'instruction' => "An 'eye' is an empty point surrounded by your stones. A group with two separate eyes cannot be captured because the opponent can't fill both simultaneously!",
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 1, 'y' => 0], ['x' => 1, 'y' => 1], ['x' => 1, 'y' => 2], ['x' => 2, 'y' => 2], ['x' => 3, 'y' => 2], ['x' => 3, 'y' => 1], ['x' => 3, 'y' => 0]],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 0, 'y' => 0], ['x' => 0, 'y' => 1]], 'type' => 'circle', 'color' => '#22c55e'],
                        ['coordinates' => [['x' => 2, 'y' => 0], ['x' => 2, 'y' => 1]], 'type' => 'circle', 'color' => '#22c55e'],
                    ],
                ],
                [
                    'id' => 'ko-5',
                    'type' => 'instruction',
                    'title' => 'Dead Stones',
                    'instruction' => "At the end of the game, stones that cannot avoid capture are 'dead' and removed. Players agree on which stones are dead during scoring.",
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 0, 'y' => 0]],
                    'initialWhite' => [['x' => 0, 'y' => 1], ['x' => 1, 'y' => 0], ['x' => 1, 'y' => 1]],
                    'highlights' => [
                        ['coordinates' => [['x' => 0, 'y' => 0]], 'type' => 'square', 'color' => '#ef4444'],
                    ],
                ],
                [
                    'id' => 'ko-6',
                    'type' => 'instruction',
                    'title' => 'Ko Mastered!',
                    'instruction' => 'You now understand the Ko rule and key special situations. Two eyes mean life, and the Ko rule prevents infinite loops. Ready for some strategy?',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                ],
            ],
        ];
    }

    private function createBasicStrategyLesson(): array
    {
        return [
            'slug' => 'basic-strategy',
            'title' => 'Basic Strategy',
            'description' => 'Discover fundamental strategic concepts to improve your game.',
            'category' => 'strategy',
            'difficulty' => 'beginner',
            'duration' => '20 min',
            'order' => 5,
            'prerequisites' => [4],
            'steps' => [
                [
                    'id' => 'strategy-1',
                    'type' => 'instruction',
                    'title' => 'Corners First',
                    'instruction' => 'The corners are the most efficient places to build territory. It takes fewer stones to surround space in corners than edges or center. Start in the corners!',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 2, 'y' => 2], ['x' => 2, 'y' => 6], ['x' => 6, 'y' => 2], ['x' => 6, 'y' => 6]], 'type' => 'circle', 'color' => '#22c55e'],
                    ],
                ],
                [
                    'id' => 'strategy-2',
                    'type' => 'placement',
                    'title' => 'Opening Move',
                    'instruction' => 'Play your first stone in or near a corner. The star points (marked dots) are traditional good opening moves.',
                    'hint' => 'Try playing on one of the corner star points.',
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                    'correctMoves' => [
                        ['x' => 2, 'y' => 2], ['x' => 2, 'y' => 6], ['x' => 6, 'y' => 2], ['x' => 6, 'y' => 6],
                        ['x' => 2, 'y' => 3], ['x' => 3, 'y' => 2], ['x' => 2, 'y' => 5], ['x' => 3, 'y' => 6],
                        ['x' => 6, 'y' => 3], ['x' => 5, 'y' => 2], ['x' => 6, 'y' => 5], ['x' => 5, 'y' => 6],
                    ],
                ],
                [
                    'id' => 'strategy-3',
                    'type' => 'instruction',
                    'title' => 'Then Sides, Then Center',
                    'instruction' => 'After corners, expand to the sides. The center is hardest to make territory in - it requires the most stones to surround space.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 2, 'y' => 2], ['x' => 6, 'y' => 6]],
                    'initialWhite' => [['x' => 6, 'y' => 2], ['x' => 2, 'y' => 6]],
                    'highlights' => [
                        ['coordinates' => [['x' => 4, 'y' => 2], ['x' => 4, 'y' => 6], ['x' => 2, 'y' => 4], ['x' => 6, 'y' => 4]], 'type' => 'circle', 'color' => '#3b82f6'],
                    ],
                ],
                [
                    'id' => 'strategy-4',
                    'type' => 'instruction',
                    'title' => 'Keep Your Stones Connected',
                    'instruction' => "Connected stones are stronger because they share liberties. Try to keep your groups linked, and look for ways to cut your opponent's stones apart.",
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 3, 'y' => 3], ['x' => 4, 'y' => 3], ['x' => 5, 'y' => 3], ['x' => 4, 'y' => 4]],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 3, 'y' => 3], ['x' => 4, 'y' => 3], ['x' => 5, 'y' => 3], ['x' => 4, 'y' => 4]], 'type' => 'circle', 'color' => '#22c55e'],
                    ],
                ],
                [
                    'id' => 'strategy-5',
                    'type' => 'instruction',
                    'title' => 'Extend from Strength',
                    'instruction' => 'From a strong group, extend to claim territory. A good rule: extend as many spaces as you have stones in a row. Two stones in a row = extend two spaces.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 2, 'y' => 6], ['x' => 3, 'y' => 6]],
                    'initialWhite' => [],
                    'highlights' => [
                        ['coordinates' => [['x' => 5, 'y' => 6]], 'type' => 'square', 'color' => '#22c55e'],
                    ],
                ],
                [
                    'id' => 'strategy-6',
                    'type' => 'puzzle',
                    'title' => 'Find the Good Extension',
                    'instruction' => 'Black has two stones on the bottom. Extend to a good point to build territory along the edge.',
                    'hint' => 'Count 2-3 spaces from your stones.',
                    'wrongMoveResponse' => 'Try extending along the edge at an appropriate distance.',
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 2, 'y' => 6], ['x' => 3, 'y' => 6]],
                    'initialWhite' => [],
                    'correctMoves' => [
                        ['x' => 5, 'y' => 6], ['x' => 6, 'y' => 6],
                    ],
                ],
                [
                    'id' => 'strategy-7',
                    'type' => 'instruction',
                    'title' => 'Balance Attack and Defense',
                    'instruction' => "Don't only attack or only defend. Respond to your opponent's threats, but also look for opportunities to expand your territory and threaten their groups.",
                    'boardSize' => 9,
                    'initialBlack' => [['x' => 2, 'y' => 2], ['x' => 2, 'y' => 6]],
                    'initialWhite' => [['x' => 6, 'y' => 2], ['x' => 6, 'y' => 6]],
                ],
                [
                    'id' => 'strategy-8',
                    'type' => 'instruction',
                    'title' => 'Congratulations!',
                    'instruction' => "You've completed the basic strategy lesson! Remember: corners first, keep stones connected, and balance attack with defense. Now go play some games!",
                    'boardSize' => 9,
                    'initialBlack' => [],
                    'initialWhite' => [],
                ],
            ],
        ];
    }
}
