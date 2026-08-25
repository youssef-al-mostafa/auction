<?php

namespace App\Exceptions;

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use Exception;

class AuctionTransitionException extends Exception
{
    public static function notAllowed(AuctionItemStatusEnum $from, AuctionItemStatusEnum $to): self
    {
        return new self("An item cannot go from {$from->value} to {$to->value}.");
    }

    public static function noItemsLeft(): self
    {
        return new self('Every item in this auction has been closed.');
    }

    public static function anotherItemIsLive(): self
    {
        return new self('Close the current item before launching the next one.');
    }

    public static function auctionNotRunning(): self
    {
        return new self('Start the auction before putting a lot under the hammer.');
    }

    public static function auctionNotAllowed(AuctionStatusEnum $from, AuctionStatusEnum $to): self
    {
        return new self("An auction cannot go from {$from->value} to {$to->value}.");
    }

    public static function itemsStillOpen(): self
    {
        return new self('Close the lot under the hammer before ending the auction.');
    }
}
