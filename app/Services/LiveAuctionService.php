<?php

namespace App\Services;

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use App\Events\AuctionStatusChanged;
use App\Events\AuctionWon;
use App\Events\CountdownStarted;
use App\Events\ItemActivated;
use App\Events\ItemSold;
use App\Exceptions\AuctionTransitionException;
use App\Jobs\CloseAuctionItem;
use App\Models\Auction;
use App\Models\AuctionItem;
use App\Models\Bid;
use App\Models\Product;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LiveAuctionService
{
    public const PAYMENT_WINDOW_HOURS = 2;

    public function defaultCountdownSeconds(): int
    {
        return (int) config('auction.countdown_seconds');
    }

    /**
     * Opens the room. Until an auction is running, no lot can be launched.
     *
     * @throws AuctionTransitionException
     */
    public function startAuction(Auction $auction): Auction
    {
        $this->assertAuctionCanTransition($auction, AuctionStatusEnum::RUNNING);

        $auction->update(['status' => AuctionStatusEnum::RUNNING]);

        AuctionStatusChanged::dispatch($auction);

        return $auction;
    }

    /**
     * @throws AuctionTransitionException
     */
    public function endAuction(Auction $auction): Auction
    {
        $this->assertAuctionCanTransition($auction, AuctionStatusEnum::ENDED);

        $stillOpen = $auction->auctionItems()
            ->whereIn('status', [
                AuctionItemStatusEnum::ACTIVE,
                AuctionItemStatusEnum::COUNTING_DOWN,
            ])
            ->exists();

        if ($stillOpen) {
            throw AuctionTransitionException::itemsStillOpen();
        }

        $auction->update(['status' => AuctionStatusEnum::ENDED]);

        AuctionStatusChanged::dispatch($auction);

        return $auction;
    }

    /**
     * Puts the next pending item under the hammer.
     *
     * @throws AuctionTransitionException
     */
    public function launchNextItem(Auction $auction): AuctionItem
    {
        if ($auction->status !== AuctionStatusEnum::RUNNING) {
            throw AuctionTransitionException::auctionNotRunning();
        }

        $item = DB::transaction(function () use ($auction): AuctionItem {
            $live = $auction->auctionItems()
                ->whereIn('status', [
                    AuctionItemStatusEnum::ACTIVE,
                    AuctionItemStatusEnum::COUNTING_DOWN,
                ])
                ->lockForUpdate()
                ->first();

            if ($live instanceof AuctionItem) {
                throw AuctionTransitionException::anotherItemIsLive();
            }

            $next = $auction->auctionItems()
                ->where('status', AuctionItemStatusEnum::PENDING)
                ->inLaunchOrder()
                ->lockForUpdate()
                ->first();

            if (! $next instanceof AuctionItem) {
                throw AuctionTransitionException::noItemsLeft();
            }

            $this->assertCanTransition($next, AuctionItemStatusEnum::ACTIVE);

            $next->update([
                'status' => AuctionItemStatusEnum::ACTIVE,
                'activated_at' => now(),
            ]);

            return $next;
        });

        ItemActivated::dispatch($item->load('product'));

        return $item;
    }

    /**
     * Starts the formal countdown and schedules the hammer to fall.
     *
     * @throws AuctionTransitionException
     */
    public function startCountdown(AuctionItem $item, ?int $seconds = null): AuctionItem
    {
        $seconds ??= $this->defaultCountdownSeconds();

        $updated = DB::transaction(function () use ($item, $seconds): AuctionItem {
            $locked = $this->lock($item);

            $this->assertCanTransition($locked, AuctionItemStatusEnum::COUNTING_DOWN);

            $locked->update([
                'status' => AuctionItemStatusEnum::COUNTING_DOWN,
                'countdown_ends_at' => now()->addSeconds($seconds),
                'countdown_seconds' => $seconds,
            ]);

            return $locked;
        });

        // Guarded by the exact timestamp: if a bid clears it or the admin restarts
        // the countdown, this job wakes to a mismatch and does nothing.
        CloseAuctionItem::dispatch($updated->id, $updated->countdown_ends_at)
            ->delay($updated->countdown_ends_at);

        CountdownStarted::dispatch($updated);

        return $updated;
    }

    /**
     * Drops the hammer. Idempotent — an already closed item is returned untouched,
     * so the delayed job, a lazy read, and an admin click cannot close it twice.
     * Only the call that actually closed it announces the win.
     */
    public function closeItem(AuctionItem $item): AuctionItem
    {
        /** @var array{item: AuctionItem, won: bool} $result */
        $result = DB::transaction(function () use ($item): array {
            $locked = $this->lock($item);

            if ($locked->status->isClosed()) {
                return ['item' => $locked, 'won' => false];
            }

            $hasWinner = $locked->current_bid_id !== null;
            $closedAt = now();

            $locked->update([
                'status' => $hasWinner
                    ? AuctionItemStatusEnum::SOLD
                    : AuctionItemStatusEnum::UNSOLD,
                'winner_id' => $locked->current_bidder_id,
                'sold_price' => $locked->current_bid,
                'countdown_ends_at' => null,
                'closed_at' => $closedAt,
                'payment_deadline' => $hasWinner
                    ? $closedAt->addHours(self::PAYMENT_WINDOW_HOURS)
                    : null,
            ]);

            return ['item' => $locked, 'won' => $hasWinner];
        });

        $closed = $result['item'];

        ItemSold::dispatch($closed->load(['product', 'winner']));

        if ($result['won']) {
            AuctionWon::dispatch($closed);
        }

        return $closed;
    }

    /**
     * Closes an item whose countdown already expired. Called on read, so a stalled
     * queue worker cannot leave an item stuck mid-countdown forever.
     */
    public function closeIfCountdownExpired(AuctionItem $item): AuctionItem
    {
        $expired = $item->status === AuctionItemStatusEnum::COUNTING_DOWN
            && $item->countdown_ends_at !== null
            && $item->countdown_ends_at->isPast();

        return $expired ? $this->closeItem($item) : $item;
    }

    /**
     * Closes an expired countdown only if it is still the one that was scheduled.
     */
    public function closeExpiredCountdown(AuctionItem $item, CarbonInterface $scheduledFor): ?AuctionItem
    {
        if ($item->countdown_ends_at === null) {
            return null;
        }

        if (! $item->countdown_ends_at->equalTo($scheduledFor)) {
            return null;
        }

        return $this->closeItem($item);
    }

    /**
     * Every item in launch order, with the item currently under the hammer closed
     * first if its countdown lapsed while nothing was watching.
     *
     * @return Collection<int, AuctionItem>
     */
    public function itemsForRoom(Auction $auction): Collection
    {
        $live = $auction->auctionItems()
            ->whereIn('status', [
                AuctionItemStatusEnum::ACTIVE,
                AuctionItemStatusEnum::COUNTING_DOWN,
            ])
            ->first();

        if ($live instanceof AuctionItem) {
            $this->closeIfCountdownExpired($live);
        }

        return $auction->auctionItems()
            ->with(['product.media', 'currentBidder', 'winner'])
            ->inLaunchOrder()
            ->get();
    }

    /**
     * The most recent bids on an item, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function recentBids(?AuctionItem $item, int $limit = 15): array
    {
        if (! $item instanceof AuctionItem) {
            return [];
        }

        return array_values(
            $item->bids()
                ->with('user')
                ->latest('created_at')
                ->latest('id')
                ->take($limit)
                ->get()
                ->map(fn (Bid $bid) => [
                    'id' => $bid->id,
                    'amount' => $bid->amount,
                    'bidder' => $bid->user->name,
                    'bidder_id' => $bid->user_id,
                    'placed_at' => $bid->created_at->toIso8601String(),
                ])
                ->all(),
        );
    }

    /**
     * The shape both the admin console and the public room read an item in.
     *
     * @return array<string, mixed>
     */
    public function toRoomItem(AuctionItem $item): array
    {
        $image = $item->product->getFirstMediaUrl(Product::IMAGES, 'large');

        return [
            'id' => $item->id,
            'position' => $item->position,
            'name' => $item->product->name,
            'description' => $item->product->description,
            'image' => $image === '' ? null : $image,
            'status' => $item->status->value,
            'starting_price' => $item->starting_price,
            'current_bid' => $item->current_bid,
            'current_bidder' => $item->currentBidder?->name,
            'current_bidder_id' => $item->current_bidder_id,
            'countdown_ends_at' => $item->countdown_ends_at?->toIso8601String(),
            'countdown_seconds' => $item->countdown_seconds,
            'sold_price' => $item->sold_price,
            'winner_name' => $item->winner?->name,
        ];
    }

    private function lock(AuctionItem $item): AuctionItem
    {
        return AuctionItem::query()
            ->whereKey($item->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @throws AuctionTransitionException
     */
    private function assertCanTransition(AuctionItem $item, AuctionItemStatusEnum $target): void
    {
        if (! $item->status->canTransitionTo($target)) {
            throw AuctionTransitionException::notAllowed($item->status, $target);
        }
    }

    /**
     * @throws AuctionTransitionException
     */
    private function assertAuctionCanTransition(Auction $auction, AuctionStatusEnum $target): void
    {
        if (! $auction->status->canTransitionTo($target)) {
            throw AuctionTransitionException::auctionNotAllowed($auction->status, $target);
        }
    }
}
