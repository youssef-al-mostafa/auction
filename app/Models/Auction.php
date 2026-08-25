<?php

namespace App\Models;

use App\Enums\AuctionStatusEnum;
use App\Enums\AuctionTypeEnum;
use Carbon\CarbonImmutable;
use Database\Factories\AuctionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $title
 * @property string $slug
 * @property AuctionTypeEnum $type
 * @property AuctionStatusEnum $status
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable|null $ends_at
 * @property int|null $auction_items_count
 */
#[Fillable(['title', 'slug', 'type', 'status', 'starts_at', 'ends_at'])]
class Auction extends Model
{
    /** @use HasFactory<AuctionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AuctionTypeEnum::class,
            'status' => AuctionStatusEnum::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $auction): void {
            $auction->slug ??= self::uniqueSlug($auction->title);
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('type', AuctionTypeEnum::LIVE);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOngoing(Builder $query): Builder
    {
        return $query->where('type', AuctionTypeEnum::ONGOING);
    }

    /**
     * @return HasMany<AuctionItem, $this>
     */
    public function auctionItems(): HasMany
    {
        return $this->hasMany(AuctionItem::class);
    }

    /**
     * @return HasMany<ChatThread, $this>
     */
    public function chatThreads(): HasMany
    {
        return $this->hasMany(ChatThread::class);
    }

    protected static function uniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        $slug = $base;
        $suffix = 2;

        while (self::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}
