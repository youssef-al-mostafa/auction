<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function index(): Response
    {
        $products = $this->products->paginateForAdmin()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'thumb' => $this->thumbUrl($product),
                'auction' => $product->currentAuctionItem?->auction?->title,
                'status' => $product->status()->value,
            ]);

        return Inertia::render('admin/products/index', [
            'products' => $products,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/products/create');
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $this->products->create(
            $request->safe()->only(['name', 'description']),
            $this->uploadedImages($request),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product created.')]);

        return to_route('admin.products.index');
    }

    public function edit(Product $product): Response
    {
        return Inertia::render('admin/products/edit', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'description' => $product->description,
                'images' => $this->images($product),
            ],
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $this->products->update(
            $product,
            $request->safe()->only(['name', 'description']),
            $this->uploadedImages($request),
            array_values(Arr::wrap($request->validated('removed_media') ?? [])),
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product updated.')]);

        return to_route('admin.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if (! $this->products->isDeletable($product)) {
            Inertia::flash('toast', [
                'type' => 'error',
                'message' => __('This product is used in an auction and cannot be deleted.'),
            ]);

            return back();
        }

        $this->products->delete($product);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Product deleted.')]);

        return to_route('admin.products.index');
    }

    /**
     * @return list<UploadedFile>
     */
    private function uploadedImages(ProductRequest $request): array
    {
        $files = $request->file('images');

        if ($files === null) {
            return [];
        }

        return array_values(Arr::wrap($files));
    }

    private function thumbUrl(Product $product): ?string
    {
        $url = $product->getFirstMediaUrl(Product::IMAGES, 'thumb');

        return $url === '' ? null : $url;
    }

    /**
     * @return list<array{id: int, url: string, name: string}>
     */
    private function images(Product $product): array
    {
        return array_values(
            $product->getMedia(Product::IMAGES)
                ->map(fn (Media $media) => [
                    'id' => $media->id,
                    'url' => $media->getUrl('small'),
                    'name' => $media->file_name,
                ])
                ->all(),
        );
    }
}
