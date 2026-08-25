<?php

namespace Database\Factories;

use App\Enums\AuctionItemStatusEnum;
use App\Models\Auction;
use App\Models\AuctionItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuctionItem>
 */
class AuctionItemFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auction_id' => Auction::factory(),
            'product_id' => Product::factory(),
            'position' => 0,
            'starting_price' => fake()->randomFloat(2, 10, 5000),
            'status' => AuctionItemStatusEnum::PENDING,
        ];
    }
}
