<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BidFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $auction_item_id
 * @property int $user_id
 * @property string $amount
 * @property string $idempotency_key
 * @property CarbonImmutable $created_at
 * @property-read AuctionItem $auctionItem
 * @property-read User $user
 */
#[Fillable(['auction_item_id', 'user_id', 'amount', 'idempotency_key'])]
class Bid extends Model
{
    /** @use HasFactory<BidFactory> */
    use HasFactory;

    /**
     * Bids are append-only, so there is nothing for an updated_at to record.
     */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeHighestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('amount')->orderByDesc('created_at');
    }

    /**
     * @return BelongsTo<AuctionItem, $this>
     */
    public function auctionItem(): BelongsTo
    {
        return $this->belongsTo(AuctionItem::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
