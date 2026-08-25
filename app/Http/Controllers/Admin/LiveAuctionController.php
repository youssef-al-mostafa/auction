<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\AuctionTransitionException;
use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuctionItem;
use App\Services\ChatService;
use App\Services\LiveAuctionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiveAuctionController extends Controller
{
    public function __construct(
        private readonly LiveAuctionService $liveAuction,
        private readonly ChatService $chat,
    ) {}

    public function console(Auction $auction): Response
    {
        $items = $this->liveAuction->itemsForRoom($auction);
        $current = $items->first(fn (AuctionItem $item) => $item->status->isOpenForBidding());

        return Inertia::render('admin/auctions/live', [
            'auction' => [
                'id' => $auction->id,
                'slug' => $auction->slug,
                'title' => $auction->title,
                'type' => $auction->type,
                'status' => $auction->status,
            ],
            'current' => $current instanceof AuctionItem
                ? $this->liveAuction->toRoomItem($current)
                : null,
            'items' => $items
                ->map(fn (AuctionItem $item) => $this->liveAuction->toRoomItem($item))
                ->values()
                ->all(),
            'bids' => $this->liveAuction->recentBids($current),
            'conversations' => $this->chat->conversations($auction),
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    public function start(Auction $auction): RedirectResponse
    {
        try {
            $this->liveAuction->startAuction($auction);
        } catch (AuctionTransitionException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back();
    }

    public function end(Auction $auction): RedirectResponse
    {
        try {
            $this->liveAuction->endAuction($auction);
        } catch (AuctionTransitionException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back();
    }

    public function launchNext(Auction $auction): RedirectResponse
    {
        try {
            $this->liveAuction->launchNextItem($auction);
        } catch (AuctionTransitionException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back();
    }

    public function startCountdown(Request $request, Auction $auction, AuctionItem $item): RedirectResponse
    {
        abort_unless($item->auction_id === $auction->id, 404);

        $validated = $request->validate([
            'seconds' => ['nullable', 'integer', 'min:3', 'max:300'],
        ]);

        try {
            $this->liveAuction->startCountdown($item, $validated['seconds'] ?? null);
        } catch (AuctionTransitionException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back();
    }

    public function close(Auction $auction, AuctionItem $item): RedirectResponse
    {
        abort_unless($item->auction_id === $auction->id, 404);

        $this->liveAuction->closeItem($item);

        return back();
    }
}
