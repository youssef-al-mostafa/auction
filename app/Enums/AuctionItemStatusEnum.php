<?php

namespace App\Enums;

enum AuctionItemStatusEnum: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case COUNTING_DOWN = 'counting_down';
    case SOLD = 'sold';
    case UNSOLD = 'unsold';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The transition graph. `sold` and `unsold` are terminal — an item that went
     * under the hammer never reopens.
     *
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::ACTIVE],
            self::ACTIVE => [self::COUNTING_DOWN, self::SOLD, self::UNSOLD],
            self::COUNTING_DOWN => [self::ACTIVE, self::SOLD, self::UNSOLD],
            self::SOLD, self::UNSOLD => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions(), true);
    }

    public function isOpenForBidding(): bool
    {
        return $this === self::ACTIVE || $this === self::COUNTING_DOWN;
    }

    public function isClosed(): bool
    {
        return $this === self::SOLD || $this === self::UNSOLD;
    }
}
