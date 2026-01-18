<?php

namespace App\Jobs;

use App\Services\MatchmakingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpandMatchmakingRange implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(MatchmakingService $matchmakingService): void
    {
        $matchmakingService->expandRatingRanges();
    }
}
