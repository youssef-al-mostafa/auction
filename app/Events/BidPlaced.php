<?php

namespace App\Events;

use App\Models\Bid;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BidPlaced implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Bid $bid) {}

    /**
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('auction.'.$this->bid->auctionItem->auction_id)];
    }

    public function broadcastAs(): string
    {
        return 'bid.placed';
    }

    /**
     * Carries the item's resulting state alongside the bid, so a client can update
     * the feed and cancel a running countdown from one message. Splitting those
     * into two events would let them arrive out of order.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $item = $this->bid->auctionItem;

        return [
            'bid' => [
                'id' => $this->bid->id,
                'amount' => $this->bid->amount,
                'bidder' => $this->bid->user->name,
                'bidder_id' => $this->bid->user_id,
                'placed_at' => $this->bid->created_at->toIso8601String(),
            ],
            'item' => [
                'id' => $item->id,
                'status' => $item->status->value,
                'current_bid' => $item->current_bid,
                'current_bidder_id' => $item->current_bidder_id,
                'countdown_ends_at' => $item->countdown_ends_at?->toIso8601String(),
            ],
            'server_time' => now()->toIso8601String(),
        ];
    }
}
