<?php

namespace Database\Factories;

use App\Models\Auction;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatThread>
 */
class ChatThreadFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'auction_id' => Auction::factory(),
            'user_id' => User::factory(),
        ];
    }

    public function publicRoom(): self
    {
        return $this->state(fn (): array => ['user_id' => null]);
    }
}
