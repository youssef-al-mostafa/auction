<?php

namespace App\Http\Controllers;

use App\Enums\AuctionStatusEnum;
use App\Models\AuctionItem;
use App\Services\AuctionService;
use App\Services\LiveAuctionService;
use Inertia\Inertia;
use Inertia\Response;

class ItemController extends Controller
{
    /**
     * An ongoing lot collects bids for days rather than the couple of minutes a
     * live lot is under the hammer, so the page shows more than the room's feed.
     */
    private const BID_HISTORY_LIMIT = 100;

    public function __construct(
        private readonly AuctionService $auctions,
        private readonly LiveAuctionService $liveAuction,
    ) {}

    public function show(AuctionItem $item): Response
    {
        $auction = $item->auction;

        abort_if($auction->status === AuctionStatusEnum::DRAFT, 404);

        $item->load(['product.media', 'currentBidder', 'winner']);

        return Inertia::render('items/show', [
            'item' => $this->liveAuction->toRoomItem($item),
            'auction' => [
                'id' => $auction->id,
                'slug' => $auction->slug,
                'title' => $auction->title,
                'type' => $auction->type,
                'status' => $auction->status,
                'ends_at' => $auction->ends_at?->toIso8601String(),
            ],
            'bids' => $this->liveAuction->recentBids($item, self::BID_HISTORY_LIMIT),
            'otherItems' => $this->auctions->otherItemsInAuction($item),
        ]);
    }
}
