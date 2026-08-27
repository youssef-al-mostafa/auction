<?php

namespace App\Services;

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use App\Enums\PermissionsEnum;
use App\Events\BidPlaced;
use App\Exceptions\BidRejectedException;
use App\Models\AuctionItem;
use App\Models\Bid;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BiddingService
{
    /**
     * Statuses during which an item will accept a bid.
     */
    private const OPEN_STATUSES = [
        AuctionItemStatusEnum::ACTIVE,
        AuctionItemStatusEnum::COUNTING_DOWN,
    ];

    public function __construct(private readonly LiveAuctionService $liveAuction) {}

    /**
     * Records a bid.
     *
     * Everything happens inside one transaction behind a row lock on the auction
     * item. Validation and insertion cannot be separated: checking that a bid is
     * the highest and then saving it in a second step leaves a window for another
     * bid to land in between, which is how an auction ends up with two winners.
     *
     * @throws BidRejectedException
     */
    public function place(
        AuctionItem $item,
        User $user,
        string $amount,
        string $idempotencyKey,
    ): Bid {
        // A countdown that ran out while the queue was stalled has to fall before a
        // late bid is judged, or the item takes a bid it should already be sold on.
        // The lock below re-reads the row, so the closed status is what gets checked.
        $this->liveAuction->closeIfCountdownExpired($item);

        $bid = DB::transaction(function () use ($item, $user, $amount, $idempotencyKey): Bid {
            $locked = AuctionItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $replay = $this->findReplay($locked, $idempotencyKey);

            if ($replay instanceof Bid) {
                return $replay;
            }

            $this->assertBidIsAcceptable($locked, $user, $amount);

            $bid = Bid::create([
                'auction_item_id' => $locked->id,
                'user_id' => $user->id,
                'amount' => $amount,
                'idempotency_key' => $idempotencyKey,
            ]);

            $locked->update([
                'current_bid' => $bid->amount,
                'current_bid_id' => $bid->id,
                'current_bidder_id' => $user->id,
                ...$this->interruptCountdown($locked),
            ]);

            return $bid;
        });

        BidPlaced::dispatch($bid->load(['user', 'auctionItem']));

        return $bid;
    }

    /**
     * The interrupt rule. A valid bid placed while the countdown is running stops
     * and clears it, in the same write that records the bid — so a bid landing
     * microseconds before expiry cannot lose to the timer.
     *
     * @return array<string, mixed>
     */
    private function interruptCountdown(AuctionItem $item): array
    {
        if ($item->status !== AuctionItemStatusEnum::COUNTING_DOWN) {
            return [];
        }

        return [
            'status' => AuctionItemStatusEnum::ACTIVE,
            'countdown_ends_at' => null,
        ];
    }

    private function findReplay(AuctionItem $item, string $idempotencyKey): ?Bid
    {
        return Bid::query()
            ->where('auction_item_id', $item->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    /**
     * @throws BidRejectedException
     */
    private function assertBidIsAcceptable(AuctionItem $item, User $user, string $amount): void
    {
        if ($user->can(PermissionsEnum::MANAGE_AUCTIONS->value)) {
            throw BidRejectedException::administratorsCannotBid();
        }

        if (! in_array($item->status, self::OPEN_STATUSES, true)) {
            throw BidRejectedException::itemNotOpen();
        }

        $auction = $item->auction;

        if ($auction->status !== AuctionStatusEnum::RUNNING) {
            throw BidRejectedException::auctionNotRunning();
        }

        if ($auction->ends_at !== null && $auction->ends_at->isPast()) {
            throw BidRejectedException::auctionExpired();
        }

        $minimum = $item->minimumBid();

        if ($this->toCents($amount) <= $this->toCents($minimum)) {
            throw BidRejectedException::tooLow($minimum);
        }
    }

    /**
     * Money is compared as integer cents. Casting decimal strings to float and
     * comparing them is how off-by-one-cent bugs get in.
     */
    private function toCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
