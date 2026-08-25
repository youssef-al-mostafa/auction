<?php

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use App\Enums\AuctionTypeEnum;
use App\Models\Auction;
use App\Models\AuctionItem;
use App\Models\Bid;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

/**
 * A running ongoing auction: every lot is open for bidding at the same time.
 */
function ongoingAuction(int $lots = 3, AuctionStatusEnum $status = AuctionStatusEnum::RUNNING): Auction
{
    $auction = Auction::factory()->create([
        'type' => AuctionTypeEnum::ONGOING,
        'status' => $status,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addWeek(),
    ]);

    foreach (range(1, $lots) as $position) {
        AuctionItem::factory()->for($auction)->for(Product::factory())->create([
            'position' => $position,
            'starting_price' => '100.00',
            'status' => AuctionItemStatusEnum::ACTIVE,
        ]);
    }

    return $auction;
}

test('an ongoing lot has its own public page', function () {
    $auction = ongoingAuction(lots: 2);
    $item = $auction->auctionItems()->inLaunchOrder()->firstOrFail();

    Bid::factory()->for($item, 'auctionItem')->create(['amount' => '150.00']);

    $this->get(route('items.show', $item))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('items/show')
            ->where('item.id', $item->id)
            ->where('item.name', $item->product->name)
            ->where('auction.type', AuctionTypeEnum::ONGOING->value)
            ->where('auction.ends_at', $auction->ends_at?->toIso8601String())
            ->has('bids', 1)
            ->where('bids.0.amount', '150.00')
            ->has('otherItems', 1)
        );
});

test('every lot in an ongoing auction is reachable, not just the first', function () {
    $auction = ongoingAuction(lots: 4);

    $auction->auctionItems->each(
        fn (AuctionItem $item) => $this->get(route('items.show', $item))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->where('item.id', $item->id))
    );
});

test('a bid placed from the item page is recorded and becomes the current bid', function () {
    $auction = ongoingAuction(lots: 2);
    $item = $auction->auctionItems()->inLaunchOrder()->firstOrFail();
    $bidder = User::factory()->create();

    $this->actingAs($bidder)
        ->from(route('items.show', $item))
        ->post(route('items.bids.store', $item), [
            'amount' => '250.00',
            'idempotency_key' => 'first-bid',
        ])
        ->assertRedirect(route('items.show', $item));

    expect(Bid::where('auction_item_id', $item->id)->count())->toBe(1);

    expect($item->refresh())
        ->current_bid->toBe('250.00')
        ->current_bidder_id->toBe($bidder->id);
});

test('a replayed idempotency key does not record a second bid', function () {
    $auction = ongoingAuction(lots: 1);
    $item = $auction->auctionItems()->firstOrFail();
    $bidder = User::factory()->create();

    $payload = ['amount' => '250.00', 'idempotency_key' => 'double-click'];

    $this->actingAs($bidder)->post(route('items.bids.store', $item), $payload);
    $this->actingAs($bidder)->post(route('items.bids.store', $item), $payload);

    expect(Bid::where('auction_item_id', $item->id)->count())->toBe(1);
});

test('a lot whose auction is still a draft is not public', function () {
    $auction = ongoingAuction(lots: 1, status: AuctionStatusEnum::DRAFT);

    $this->get(route('items.show', $auction->auctionItems()->firstOrFail()))
        ->assertNotFound();
});

test('a live lot keeps its page but carries the type that routes bidding to the room', function () {
    $auction = Auction::factory()->live()->create([
        'status' => AuctionStatusEnum::RUNNING,
        'starts_at' => now()->subHour(),
    ]);

    $item = AuctionItem::factory()->for($auction)->for(Product::factory())->create([
        'status' => AuctionItemStatusEnum::ACTIVE,
    ]);

    $this->get(route('items.show', $item))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auction.type', AuctionTypeEnum::LIVE->value)
            ->where('auction.slug', $auction->slug)
        );
});
