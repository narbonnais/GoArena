<?php

use App\Jobs\CheckGameTimeouts;
use App\Jobs\CleanupAbandonedGames;
use App\Jobs\ExpandMatchmakingRange;
use App\Jobs\ProcessMatchmaking;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Matchmaking processing - run every second
Schedule::job(new ProcessMatchmaking)->everySecond();

// Expand matchmaking range for players waiting too long - every 10 seconds
Schedule::job(new ExpandMatchmakingRange)->everyTenSeconds();

// Check for game timeouts - every second
Schedule::job(new CheckGameTimeouts)->everySecond();

// Cleanup abandoned games (disconnected > 2 minutes) - every minute
Schedule::job(new CleanupAbandonedGames)->everyMinute();
