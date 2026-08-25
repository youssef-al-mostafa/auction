<?php

namespace App\Services;

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductService
{
    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function paginateForAdmin(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with(['media', 'currentAuctionItem.auction:id,title,slug'])
            ->withExists([
                'auctionItems as sold_exists' => fn (Builder $query) => $query
                    ->where('status', AuctionItemStatusEnum::SOLD),
                'auctionItems as in_auction_exists' => fn (Builder $query) => $query
                    ->whereRelation('auction', 'status', '!=', AuctionStatusEnum::ENDED->value),
            ])
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<UploadedFile>  $images
     */
    public function create(array $attributes, array $images = []): Product
    {
        $product = Product::create($attributes);

        $this->addImages($product, $images);

        return $product;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  list<UploadedFile>  $images
     * @param  list<int>  $removedMediaIds
     */
    public function update(
        Product $product,
        array $attributes,
        array $images = [],
        array $removedMediaIds = [],
    ): Product {
        $product->update($attributes);

        $this->removeImages($product, $removedMediaIds);
        $this->addImages($product, $images);

        return $product->refresh();
    }

    public function isDeletable(Product $product): bool
    {
        return ! $product->auctionItems()->exists();
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    /**
     * @param  list<UploadedFile>  $images
     */
    private function addImages(Product $product, array $images): void
    {
        foreach ($images as $image) {
            $product->addMedia($image)->toMediaCollection(Product::IMAGES);
        }
    }

    /**
     * @param  list<int>  $mediaIds
     */
    private function removeImages(Product $product, array $mediaIds): void
    {
        if ($mediaIds === []) {
            return;
        }

        $product->media()
            ->where('collection_name', Product::IMAGES)
            ->whereIn('id', $mediaIds)
            ->each(fn ($media) => $media->delete());
    }
}
