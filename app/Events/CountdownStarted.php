<?php

namespace App\Events;

use App\Models\AuctionItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CountdownStarted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AuctionItem $item) {}

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('auction.'.$this->item->auction_id)];
    }

    public function broadcastAs(): string
    {
        return 'countdown.started';
    }

    /**
     * `countdown_ends_at` is absolute and `server_time` accompanies it, so each
     * client can correct for its own clock skew instead of trusting a duration.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'item' => [
                'id' => $this->item->id,
                'status' => $this->item->status->value,
                'countdown_ends_at' => $this->item->countdown_ends_at?->toIso8601String(),
                'countdown_seconds' => $this->item->countdown_seconds,
            ],
            'server_time' => now()->toIso8601String(),
        ];
    }
}
