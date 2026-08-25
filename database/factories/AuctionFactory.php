<?php

namespace Database\Factories;

use App\Enums\AuctionStatusEnum;
use App\Enums\AuctionTypeEnum;
use App\Models\Auction;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Auction>
 */
class AuctionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->unique()->sentence(3),
            'type' => AuctionTypeEnum::ONGOING,
            'status' => AuctionStatusEnum::DRAFT,
            'starts_at' => fake()->dateTimeBetween('+1 day', '+2 weeks'),
            'ends_at' => null,
        ];
    }

    public function live(): static
    {
        return $this->state(['type' => AuctionTypeEnum::LIVE]);
    }

    public function ongoing(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => AuctionTypeEnum::ONGOING,
            'ends_at' => Carbon::parse($attributes['starts_at'])->addWeek(),
        ]);
    }

    public function scheduled(): static
    {
        return $this->state(['status' => AuctionStatusEnum::SCHEDULED]);
    }
}
