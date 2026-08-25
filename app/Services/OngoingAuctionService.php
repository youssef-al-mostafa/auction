<?php

namespace App\Services;

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use App\Models\Auction;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ongoing auctions have no auctioneer. Nobody drops the hammer, so the only
 * thing that can close them is their own `ends_at` passing.
 *
 * @phpstan-type ClosedAuctionReport array{auction: Auction, sold: int, unsold: int}
 */
class OngoingAuctionService
{
    /**
     * @var list<AuctionItemStatusEnum>
     */
    private const OPEN_STATUSES = [
        AuctionItemStatusEnum::ACTIVE,
        AuctionItemStatusEnum::COUNTING_DOWN,
    ];

    public function __construct(private readonly LiveAuctionService $liveAuction) {}

    /**
     * Closes every running ongoing auction whose end date has passed.
     *
     * Safe to run repeatedly: the auction leaves `running` on the first pass, so
     * the second finds nothing, and `closeItem()` is itself idempotent.
     *
     * @return list<ClosedAuctionReport>
     */
    public function closeExpired(): array
    {
        return array_values(
            $this->expired(AuctionStatusEnum::RUNNING)
                ->get()
                ->map(fn (Auction $auction): array => $this->close($auction))
                ->all(),
        );
    }

    /**
     * Ongoing auctions whose end date passed while they were still only scheduled.
     *
     * Deliberately not closed. `AuctionStatusEnum` allows `scheduled` to reach
     * `ended` only through `running`, and forcing that route would broadcast an
     * auction opening that never happened — for an event with no bids to award,
     * since its lots are all still `pending`. Reported instead so an admin can
     * start it or take it down.
     */
    public function expiredScheduledCount(): int
    {
        return $this->expired(AuctionStatusEnum::SCHEDULED)->count();
    }

    /**
     * @return ClosedAuctionReport
     */
    private function close(Auction $auction): array
    {
        $sold = 0;
        $unsold = 0;

        $openItems = $auction->auctionItems()
            ->whereIn('status', self::OPEN_STATUSES)
            ->get();

        foreach ($openItems as $item) {
            $closed = $this->liveAuction->closeItem($item);

            if ($closed->status === AuctionItemStatusEnum::SOLD) {
                $sold++;

                continue;
            }

            $unsold++;
        }

        return [
            'auction' => $this->liveAuction->endAuction($auction),
            'sold' => $sold,
            'unsold' => $unsold,
        ];
    }

    /**
     * A null `ends_at` is a live auction, which closes by countdown rather than
     * by date, and never matches here.
     *
     * @return Builder<Auction>
     */
    private function expired(AuctionStatusEnum $status): Builder
    {
        return Auction::query()
            ->ongoing()
            ->where('status', $status)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<=', now());
    }
}
