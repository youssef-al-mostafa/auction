<?php

namespace App\Events;

use App\Models\AuctionItem;
use App\Models\Product;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemActivated implements ShouldBroadcastNow
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
        return 'item.activated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $image = $this->item->product->getFirstMediaUrl(Product::IMAGES, 'large');

        return [
            'item' => [
                'id' => $this->item->id,
                'position' => $this->item->position,
                'name' => $this->item->product->name,
                'description' => $this->item->product->description,
                'image' => $image === '' ? null : $image,
                'status' => $this->item->status->value,
                'starting_price' => $this->item->starting_price,
                'current_bid' => $this->item->current_bid,
                'current_bidder_id' => $this->item->current_bidder_id,
                'countdown_ends_at' => null,
            ],
            'server_time' => now()->toIso8601String(),
        ];
    }
}
