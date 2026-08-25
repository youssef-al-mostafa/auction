<?php

namespace App\Models;

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use App\Enums\ProductStatusEnum;
use Carbon\CarbonImmutable;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property bool|null $sold_exists
 * @property bool|null $in_auction_exists
 */
#[Fillable(['name', 'description'])]
class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, InteractsWithMedia;

    public const IMAGES = 'images';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::IMAGES)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->nonQueued()
            ->width(100);

        $this->addMediaConversion('small')
            ->nonQueued()
            ->width(480);

        $this->addMediaConversion('large')
            ->nonQueued()
            ->width(1200);
    }

    /**
     * @return HasMany<AuctionItem, $this>
     */
    public function auctionItems(): HasMany
    {
        return $this->hasMany(AuctionItem::class);
    }

    /**
     * @return HasOne<AuctionItem, $this>
     */
    public function currentAuctionItem(): HasOne
    {
        return $this->hasOne(AuctionItem::class)->latestOfMany();
    }

    /**
     * Uses the `withExists` flags when the caller preloaded them, and falls back
     * to its own queries when it did not, so a single product reads the same as
     * one pulled from a paginated list.
     */
    public function status(): ProductStatusEnum
    {
        $sold = $this->sold_exists
            ?? $this->auctionItems()->where('status', AuctionItemStatusEnum::SOLD)->exists();

        if ($sold) {
            return ProductStatusEnum::SOLD;
        }

        $inAuction = $this->in_auction_exists
            ?? $this->auctionItems()
                ->whereRelation('auction', 'status', '!=', AuctionStatusEnum::ENDED->value)
                ->exists();

        return $inAuction
            ? ProductStatusEnum::IN_AUCTION
            : ProductStatusEnum::AVAILABLE;
    }
}
