<?php

namespace App\Events;

use App\Models\AuctionItem;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemSold implements ShouldBroadcastNow
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
        return 'item.sold';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'item' => [
                'id' => $this->item->id,
                'name' => $this->item->product->name,
                'status' => $this->item->status->value,
                'sold_price' => $this->item->sold_price,
                'winner_id' => $this->item->winner_id,
                'winner_name' => $this->item->winner?->name,
                'countdown_ends_at' => null,
            ],
            'server_time' => now()->toIso8601String(),
        ];
    }
}
