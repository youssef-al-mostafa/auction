<?php

namespace App\Console\Commands;

use App\Services\OngoingAuctionService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('auctions:close-expired')]
#[Description('Close ongoing auctions whose end date has passed, awarding each lot to its highest bidder')]
class CloseExpiredAuctions extends Command
{
    public function handle(OngoingAuctionService $ongoingAuctions): int
    {
        $closed = $ongoingAuctions->closeExpired();

        foreach ($closed as $report) {
            $this->components->twoColumnDetail(
                $report['auction']->title,
                sprintf('%d sold, %d unsold', $report['sold'], $report['unsold']),
            );
        }

        $this->components->info(sprintf(
            '%d expired ongoing auction(s) closed.',
            count($closed),
        ));

        $stranded = $ongoingAuctions->expiredScheduledCount();

        if ($stranded > 0) {
            $this->components->warn(sprintf(
                '%d scheduled ongoing auction(s) passed their end date without ever running. Left untouched — an admin must start or remove them.',
                $stranded,
            ));
        }

        return self::SUCCESS;
    }
}
