<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameScoringEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array<int, array{x: int, y: int}>  $deadStones
     * @param  array{black: bool, white: bool}  $scoreAcceptance
     * @param  array{black: float, white: float}  $scores
     */
    public function __construct(
        public int $gameId,
        public string $scorePhase,
        public array $deadStones,
        public array $scoreAcceptance,
        public array $scores
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PresenceChannel("game.{$this->gameId}"),
            new Channel("game.{$this->gameId}.spectate"),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'score_phase' => $this->scorePhase,
            'dead_stones' => $this->deadStones,
            'score_acceptance' => $this->scoreAcceptance,
            'scores' => $this->scores,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'game.scoring';
    }
}
