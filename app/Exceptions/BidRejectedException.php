<?php

namespace App\Exceptions;

use Exception;

class BidRejectedException extends Exception
{
    public static function itemNotOpen(): self
    {
        return new self('This item is not open for bidding.');
    }

    public static function auctionNotRunning(): self
    {
        return new self('This auction is not currently running.');
    }

    public static function auctionExpired(): self
    {
        return new self('This auction has ended.');
    }

    public static function tooLow(string $minimum): self
    {
        return new self("Your bid must be higher than {$minimum}.");
    }

    public static function administratorsCannotBid(): self
    {
        return new self('Administrators cannot bid on auctions they run.');
    }
}
