<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameMoveEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param  array{x: int, y: int}|null  $coordinate
     * @param  array<array{x: int, y: int}>  $captures
     */
    public function __construct(
        public int $gameId,
        public ?array $coordinate,
        public string $stone,
        public int $moveNumber,
        public int $blackTimeRemainingMs,
        public int $whiteTimeRemainingMs,
        public array $captures = []
    ) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Broadcast to both the game channel (players) and spectate channel (spectators)
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
            'coordinate' => $this->coordinate,
            'stone' => $this->stone,
            'move_number' => $this->moveNumber,
            'black_time_remaining_ms' => $this->blackTimeRemainingMs,
            'white_time_remaining_ms' => $this->whiteTimeRemainingMs,
            'captures' => $this->captures,
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'game.move';
    }
}
