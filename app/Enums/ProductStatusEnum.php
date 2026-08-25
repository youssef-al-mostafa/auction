<?php

namespace App\Enums;

enum ProductStatusEnum: string
{
    case AVAILABLE = 'available';
    case IN_AUCTION = 'in_auction';
    case SOLD = 'sold';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function isRelistable(): bool
    {
        return $this === self::AVAILABLE;
    }
}
