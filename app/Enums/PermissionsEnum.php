<?php

namespace App\Enums;

enum PermissionsEnum: string
{
    case MANAGE_PRODUCTS = 'manage-products';
    case MANAGE_AUCTIONS = 'manage-auctions';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
