<?php

namespace App\Enums;

enum AuctionTypeEnum: string
{
    case ONGOING = 'ongoing';
    case LIVE = 'live';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
