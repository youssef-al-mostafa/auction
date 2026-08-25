<?php

namespace App\Services;

use App\Enums\AuctionStatusEnum;
use App\Enums\AuctionTypeEnum;
use App\Models\Auction;
use App\Models\AuctionItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @phpstan-type StorefrontItem array{
 *     id: int,
 *     name: string,
 *     description: string|null,
 *     image: string|null,
 *     status: string,
 *     starting_price: string,
 *     current_bid: string|null,
 *     auction: array{
 *         title: string,
 *         slug: string,
 *         type: string,
 *         status: string,
 *         starts_at: string|null,
 *         ends_at: string|null,
 *     },
 * }
 */
class AuctionService
{
    /**
     * @return LengthAwarePaginator<int, Auction>
     */
    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Auction::query()
            ->withCount('auctionItems')
            ->latest('starts_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Auction
    {
        return Auction::create($attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(Auction $auction, array $attributes): Auction
    {
        $auction->update($attributes);

        return $auction->refresh();
    }

    public function delete(Auction $auction): void
    {
        $auction->delete();
    }

    /**
     * Live auctions on air now or opening soon, nearest start first.
     *
     * @return Collection<int, Auction>
     */
    public function liveAndUpcoming(int $limit = 4): Collection
    {
        return Auction::query()
            ->where('type', AuctionTypeEnum::LIVE)
            ->whereIn('status', [AuctionStatusEnum::RUNNING, AuctionStatusEnum::SCHEDULED])
            ->withCount('auctionItems')
            ->orderBy('starts_at')
            ->take($limit)
            ->get();
    }

    /**
     * A sample of items drawn from every auction that is currently open.
     *
     * @return list<StorefrontItem>
     */
    public function featuredItems(int $limit = 8): array
    {
        return array_values(
            $this->storefrontItems()
                ->take($limit)
                ->get()
                ->map(fn (AuctionItem $item): array => $this->toStorefrontItem($item))
                ->all(),
        );
    }

    /**
     * The public browse grid: every item belonging to an auction that is open.
     *
     * @return LengthAwarePaginator<int, AuctionItem>
     */
    public function paginateItemsForStorefront(?string $search = null, int $perPage = 30): LengthAwarePaginator
    {
        return $this->storefrontItems()
            ->when(
                $search,
                fn (Builder $query, string $term) => $query->whereRelation(
                    'product',
                    fn (Builder $product) => $product
                        ->where('name', 'ilike', '%'.$term.'%')
                        ->orWhere('description', 'ilike', '%'.$term.'%'),
                ),
            )
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * The rest of the lots in an item's auction, so a visitor can move between
     * them without going back to the grid.
     *
     * @return list<StorefrontItem>
     */
    public function otherItemsInAuction(AuctionItem $item, int $limit = 8): array
    {
        return array_values(
            AuctionItem::query()
                ->with(['product.media', 'auction'])
                ->where('auction_id', $item->auction_id)
                ->whereKeyNot($item->getKey())
                ->inLaunchOrder()
                ->take($limit)
                ->get()
                ->map(fn (AuctionItem $other): array => $this->toStorefrontItem($other))
                ->all(),
        );
    }

    /**
     * @return StorefrontItem
     */
    public function toStorefrontItem(AuctionItem $item): array
    {
        $product = $item->product;
        $image = $product->getFirstMediaUrl(Product::IMAGES, 'small');
        $auction = $item->auction;

        return [
            'id' => $item->id,
            'name' => $product->name,
            'description' => $product->description,
            'image' => $image === '' ? null : $image,
            'status' => $item->status->value,
            'starting_price' => (string) $item->starting_price,
            'current_bid' => $item->current_bid,
            'auction' => [
                'title' => $auction->title,
                'slug' => $auction->slug,
                'type' => $auction->type->value,
                'status' => $auction->status->value,
                'starts_at' => $auction->starts_at->toIso8601String(),
                'ends_at' => $auction->ends_at?->toIso8601String(),
            ],
        ];
    }

    /**
     * Items a visitor is allowed to see — anything belonging to a running auction.
     *
     * @return Builder<AuctionItem>
     */
    private function storefrontItems(): Builder
    {
        return AuctionItem::query()
            ->with(['product.media', 'auction'])
            ->whereRelation('auction', 'status', AuctionStatusEnum::RUNNING)
            ->inLaunchOrder();
    }
}
