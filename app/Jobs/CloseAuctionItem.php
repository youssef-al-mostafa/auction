<?php

namespace App\Jobs;

use App\Models\AuctionItem;
use App\Services\LiveAuctionService;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CloseAuctionItem implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $auctionItemId,
        public CarbonInterface $scheduledFor,
    ) {}

    public function handle(LiveAuctionService $liveAuction): void
    {
        $item = AuctionItem::find($this->auctionItemId);

        if (! $item instanceof AuctionItem) {
            return;
        }

        $liveAuction->closeExpiredCountdown($item, $this->scheduledFor);
    }
}
