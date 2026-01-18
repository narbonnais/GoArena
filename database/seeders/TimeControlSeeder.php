<?php

namespace Database\Seeders;

use App\Models\TimeControl;
use Illuminate\Database\Seeder;

class TimeControlSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $timeControls = [
            // Bullet (very fast)
            [
                'name' => 'Bullet 3+0',
                'slug' => 'bullet-3-0',
                'initial_time_seconds' => 180, // 3 minutes
                'increment_seconds' => 0,
            ],
            [
                'name' => 'Bullet 3+2',
                'slug' => 'bullet-3-2',
                'initial_time_seconds' => 180, // 3 minutes
                'increment_seconds' => 2,
            ],

            // Blitz (fast)
            [
                'name' => 'Blitz 5+0',
                'slug' => 'blitz-5-0',
                'initial_time_seconds' => 300, // 5 minutes
                'increment_seconds' => 0,
            ],
            [
                'name' => 'Blitz 5+3',
                'slug' => 'blitz-5-3',
                'initial_time_seconds' => 300, // 5 minutes
                'increment_seconds' => 3,
            ],
            [
                'name' => 'Blitz 5+5',
                'slug' => 'blitz-5-5',
                'initial_time_seconds' => 300, // 5 minutes
                'increment_seconds' => 5,
            ],

            // Rapid (medium)
            [
                'name' => 'Rapid 10+5',
                'slug' => 'rapid-10-5',
                'initial_time_seconds' => 600, // 10 minutes
                'increment_seconds' => 5,
            ],
            [
                'name' => 'Rapid 10+10',
                'slug' => 'rapid-10-10',
                'initial_time_seconds' => 600, // 10 minutes
                'increment_seconds' => 10,
            ],
            [
                'name' => 'Rapid 15+10',
                'slug' => 'rapid-15-10',
                'initial_time_seconds' => 900, // 15 minutes
                'increment_seconds' => 10,
            ],

            // Classical (long)
            [
                'name' => 'Classical 30+10',
                'slug' => 'classical-30-10',
                'initial_time_seconds' => 1800, // 30 minutes
                'increment_seconds' => 10,
            ],
            [
                'name' => 'Classical 30+30',
                'slug' => 'classical-30-30',
                'initial_time_seconds' => 1800, // 30 minutes
                'increment_seconds' => 30,
            ],
            [
                'name' => 'Classical 60+30',
                'slug' => 'classical-60-30',
                'initial_time_seconds' => 3600, // 60 minutes
                'increment_seconds' => 30,
            ],
        ];

        foreach ($timeControls as $control) {
            TimeControl::updateOrCreate(
                ['slug' => $control['slug']],
                $control
            );
        }
    }
}
