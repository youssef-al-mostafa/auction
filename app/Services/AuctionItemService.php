<?php

namespace App\Services;

use App\Enums\AuctionItemStatusEnum;
use App\Models\Auction;
use App\Models\AuctionItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class AuctionItemService
{
    /**
     * @return Collection<int, AuctionItem>
     */
    public function forAuction(Auction $auction): Collection
    {
        return $auction->auctionItems()
            ->with('product.media')
            ->inLaunchOrder()
            ->get();
    }

    /**
     * @return Collection<int, Product>
     */
    public function availableProducts(): Collection
    {
        return Product::query()
            ->whereNotIn(
                'id',
                AuctionItem::query()->blockingRelisting()->select('product_id'),
            )
            ->orderBy('name')
            ->get();
    }

    public function isProductAvailable(Product $product): bool
    {
        return ! AuctionItem::query()
            ->whereBelongsTo($product)
            ->blockingRelisting()
            ->exists();
    }

    public function attach(Auction $auction, Product $product, string $startingPrice): AuctionItem
    {
        return $auction->auctionItems()->create([
            'product_id' => $product->id,
            'position' => $this->nextPosition($auction),
            'starting_price' => $startingPrice,
            'status' => AuctionItemStatusEnum::PENDING,
        ]);
    }

    public function detach(AuctionItem $item): void
    {
        $item->delete();
    }

    /**
     * Swaps the item with its neighbour so the admin can set launch order.
     */
    public function move(AuctionItem $item, int $direction): void
    {
        $neighbour = $item->auction
            ->auctionItems()
            ->where('id', '!=', $item->id)
            ->when(
                $direction < 0,
                fn ($query) => $query->where('position', '<=', $item->position)
                    ->orderByDesc('position')
                    ->orderByDesc('id'),
                fn ($query) => $query->where('position', '>=', $item->position)
                    ->orderBy('position')
                    ->orderBy('id'),
            )
            ->first();

        if (! $neighbour instanceof AuctionItem) {
            return;
        }

        DB::transaction(function () use ($item, $neighbour): void {
            $itemPosition = $item->position;

            $item->update(['position' => $neighbour->position]);
            $neighbour->update(['position' => $itemPosition]);
        });

        $this->normalise($item->auction);
    }

    private function nextPosition(Auction $auction): int
    {
        return (int) $auction->auctionItems()->max('position') + 1;
    }

    /**
     * Seeded rows can share a position, which would make swapping a no-op.
     */
    private function normalise(Auction $auction): void
    {
        $auction->auctionItems()
            ->inLaunchOrder()
            ->get()
            ->each(fn (AuctionItem $item, int $index) => $item->update([
                'position' => $index + 1,
            ]));
    }
}
