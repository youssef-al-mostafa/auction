<?php

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use App\Events\AuctionWon;
use App\Events\ItemSold;
use App\Models\Auction;
use App\Models\AuctionItem;
use App\Models\Bid;
use App\Models\User;
use App\Services\LiveAuctionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

function expiredOngoingAuctionItem(): AuctionItem
{
    $auction = Auction::factory()->ongoing()->create([
        'status' => AuctionStatusEnum::RUNNING,
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->subMinute(),
    ]);

    return AuctionItem::factory()->create([
        'auction_id' => $auction->id,
        'status' => AuctionItemStatusEnum::ACTIVE,
        'starting_price' => '100.00',
    ]);
}

function highestBidOn(AuctionItem $item, string $amount): Bid
{
    $bid = Bid::factory()->create([
        'auction_item_id' => $item->id,
        'amount' => $amount,
    ]);

    $item->update([
        'current_bid' => $bid->amount,
        'current_bid_id' => $bid->id,
        'current_bidder_id' => $bid->user_id,
    ]);

    return $bid;
}

test('an expired ongoing auction ends and awards the lot it has bids on', function () {
    Event::fake([ItemSold::class, AuctionWon::class]);

    $item = expiredOngoingAuctionItem();
    $bid = highestBidOn($item, '250.00');

    $this->artisan('auctions:close-expired')->assertSuccessful();

    $item->refresh();

    expect($item->status)->toBe(AuctionItemStatusEnum::SOLD)
        ->and($item->winner_id)->toBe($bid->user_id)
        ->and($item->sold_price)->toBe('250.00')
        ->and($item->closed_at)->not->toBeNull()
        ->and($item->payment_deadline->equalTo(
            $item->closed_at->addHours(LiveAuctionService::PAYMENT_WINDOW_HOURS)
        ))->toBeTrue()
        ->and($item->auction->refresh()->status)->toBe(AuctionStatusEnum::ENDED);

    Event::assertDispatched(ItemSold::class);
    Event::assertDispatched(AuctionWon::class);
});

test('a lot nobody bid on closes unsold with no winner', function () {
    $item = expiredOngoingAuctionItem();

    $this->artisan('auctions:close-expired')->assertSuccessful();

    $item->refresh();

    expect($item->status)->toBe(AuctionItemStatusEnum::UNSOLD)
        ->and($item->winner_id)->toBeNull()
        ->and($item->sold_price)->toBeNull()
        ->and($item->payment_deadline)->toBeNull();
});

test('running the command twice changes nothing and does not re-notify the winner', function () {
    $item = expiredOngoingAuctionItem();
    highestBidOn($item, '250.00');

    $this->artisan('auctions:close-expired')->assertSuccessful();

    $first = $item->fresh();

    Event::fake([ItemSold::class, AuctionWon::class]);

    $this->artisan('auctions:close-expired')->assertSuccessful();

    $second = $item->fresh();

    expect($second->status)->toBe($first->status)
        ->and($second->winner_id)->toBe($first->winner_id)
        ->and($second->sold_price)->toBe($first->sold_price)
        ->and($second->closed_at->equalTo($first->closed_at))->toBeTrue()
        ->and($second->payment_deadline->equalTo($first->payment_deadline))->toBeTrue();

    Event::assertNotDispatched(ItemSold::class);
    Event::assertNotDispatched(AuctionWon::class);
});

test('the winner sees the closed lot in their won items', function () {
    $item = expiredOngoingAuctionItem();
    $bid = highestBidOn($item, '250.00');

    $this->artisan('auctions:close-expired')->assertSuccessful();

    $winner = User::findOrFail($bid->user_id);

    expect($winner->wonItems()->pluck('auction_items.id')->all())->toContain($item->id);
});

test('a live auction is left alone', function () {
    $auction = Auction::factory()->live()->create([
        'status' => AuctionStatusEnum::RUNNING,
        'starts_at' => now()->subWeek(),
        'ends_at' => null,
    ]);

    $item = AuctionItem::factory()->create([
        'auction_id' => $auction->id,
        'status' => AuctionItemStatusEnum::ACTIVE,
    ]);

    $this->artisan('auctions:close-expired')->assertSuccessful();

    expect($auction->refresh()->status)->toBe(AuctionStatusEnum::RUNNING)
        ->and($item->refresh()->status)->toBe(AuctionItemStatusEnum::ACTIVE);
});

test('an ongoing auction that has not expired yet is left alone', function () {
    $auction = Auction::factory()->ongoing()->create([
        'status' => AuctionStatusEnum::RUNNING,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    AuctionItem::factory()->create([
        'auction_id' => $auction->id,
        'status' => AuctionItemStatusEnum::ACTIVE,
    ]);

    $this->artisan('auctions:close-expired')->assertSuccessful();

    expect($auction->refresh()->status)->toBe(AuctionStatusEnum::RUNNING);
});

test('an expired auction that never started is reported but not closed', function () {
    $auction = Auction::factory()->ongoing()->scheduled()->create([
        'starts_at' => now()->subWeek(),
        'ends_at' => now()->subMinute(),
    ]);

    $this->artisan('auctions:close-expired')
        ->expectsOutputToContain('1 scheduled ongoing auction(s) passed their end date')
        ->assertSuccessful();

    expect($auction->refresh()->status)->toBe(AuctionStatusEnum::SCHEDULED);
});
