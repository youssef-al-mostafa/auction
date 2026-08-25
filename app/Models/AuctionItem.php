<?php

namespace App\Models;

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use App\Enums\PaymentStatusEnum;
use Carbon\CarbonImmutable;
use Database\Factories\AuctionItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $auction_id
 * @property int $product_id
 * @property int $position
 * @property string $starting_price
 * @property string|null $current_bid
 * @property int|null $current_bid_id
 * @property int|null $current_bidder_id
 * @property AuctionItemStatusEnum $status
 * @property CarbonImmutable|null $countdown_ends_at
 * @property int|null $countdown_seconds
 * @property int|null $winner_id
 * @property string|null $sold_price
 * @property CarbonImmutable|null $activated_at
 * @property CarbonImmutable|null $closed_at
 * @property CarbonImmutable|null $payment_deadline
 * @property CarbonImmutable|null $paid_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read Auction $auction
 * @property-read Product $product
 * @property-read Bid|null $currentBid
 * @property-read User|null $currentBidder
 * @property-read User|null $winner
 */
#[Fillable([
    'auction_id',
    'product_id',
    'position',
    'starting_price',
    'current_bid',
    'current_bid_id',
    'current_bidder_id',
    'status',
    'countdown_ends_at',
    'countdown_seconds',
    'winner_id',
    'sold_price',
    'activated_at',
    'closed_at',
    'payment_deadline',
    'paid_at',
])]
class AuctionItem extends Model
{
    /** @use HasFactory<AuctionItemFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AuctionItemStatusEnum::class,
            'starting_price' => 'decimal:2',
            'current_bid' => 'decimal:2',
            'sold_price' => 'decimal:2',
            'countdown_ends_at' => 'datetime',
            'activated_at' => 'datetime',
            'closed_at' => 'datetime',
            'payment_deadline' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInLaunchOrder(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('id');
    }

    /**
     * Rows that keep a product off the shelf: a lot still in an auction that has
     * not ended, or one that actually sold — ownership transferred, so there is
     * nothing left to relist. Only lots nobody won return to the pool.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeBlockingRelisting(Builder $query): Builder
    {
        return $query->where(
            fn (Builder $item) => $item
                ->whereRelation('auction', 'status', '!=', AuctionStatusEnum::ENDED->value)
                ->orWhere('status', AuctionItemStatusEnum::SOLD),
        );
    }

    /**
     * @return BelongsTo<Auction, $this>
     */
    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Every bid ever placed on this item, winning or not.
     *
     * @return HasMany<Bid, $this>
     */
    public function bids(): HasMany
    {
        return $this->hasMany(Bid::class);
    }

    /**
     * @return BelongsTo<Bid, $this>
     */
    public function currentBid(): BelongsTo
    {
        return $this->belongsTo(Bid::class, 'current_bid_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function currentBidder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'current_bidder_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    /**
     * The floor a new bid must beat: the current high, or the starting price
     * when nobody has bid yet.
     */
    public function minimumBid(): string
    {
        return $this->current_bid ?? $this->starting_price;
    }

    public function paymentStatus(): ?PaymentStatusEnum
    {
        if ($this->payment_deadline === null) {
            return null;
        }

        return match (true) {
            $this->paid_at !== null => PaymentStatusEnum::PAID,
            $this->payment_deadline->isPast() => PaymentStatusEnum::EXPIRED,
            default => PaymentStatusEnum::PENDING,
        };
    }

    public function isPayable(): bool
    {
        return $this->paymentStatus() === PaymentStatusEnum::PENDING;
    }
}
