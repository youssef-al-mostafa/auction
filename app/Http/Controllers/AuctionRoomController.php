<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Models\AuctionItem;
use App\Services\ChatService;
use App\Services\LiveAuctionService;
use Inertia\Inertia;
use Inertia\Response;

class AuctionRoomController extends Controller
{
    public function __construct(
        private readonly LiveAuctionService $liveAuction,
        private readonly ChatService $chat,
    ) {}

    public function show(Auction $auction): Response
    {
        $items = $this->liveAuction->itemsForRoom($auction);
        $current = $items->first(fn (AuctionItem $item) => $item->status->isOpenForBidding());

        return Inertia::render('auctions/room', [
            'auction' => [
                'id' => $auction->id,
                'slug' => $auction->slug,
                'title' => $auction->title,
                'type' => $auction->type,
                'status' => $auction->status,
                'starts_at' => $auction->starts_at->toIso8601String(),
            ],
            'current' => $current instanceof AuctionItem
                ? $this->liveAuction->toRoomItem($current)
                : null,
            'items' => $items
                ->map(fn (AuctionItem $item) => $this->liveAuction->toRoomItem($item))
                ->values()
                ->all(),
            'bids' => $this->liveAuction->recentBids($current),
            'chat' => $this->chat->roomChat($auction),
            'serverTime' => now()->toIso8601String(),
        ]);
    }
}
