<?php

namespace App\Events;

use App\Models\AuctionItem;
use App\Services\CheckoutService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AuctionWon implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public AuctionItem $item) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->item->winner_id)];
    }

    public function broadcastAs(): string
    {
        return 'auction.won';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'win' => app(CheckoutService::class)->toWonItem($this->item),
            'server_time' => now()->toIso8601String(),
        ];
    }
}
