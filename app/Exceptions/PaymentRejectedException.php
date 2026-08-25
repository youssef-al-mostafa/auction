<?php

namespace App\Exceptions;

use Exception;

class PaymentRejectedException extends Exception
{
    public static function notTheWinner(): self
    {
        return new self('You did not win this item.');
    }

    public static function alreadyPaid(): self
    {
        return new self('This item has already been paid for.');
    }

    public static function deadlinePassed(): self
    {
        return new self('The payment deadline for this item has passed.');
    }

    public static function notWon(): self
    {
        return new self('This item is not awaiting payment.');
    }
}
