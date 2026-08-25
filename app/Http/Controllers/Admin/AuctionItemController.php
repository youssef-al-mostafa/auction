<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuctionItemRequest;
use App\Models\Auction;
use App\Models\AuctionItem;
use App\Models\Product;
use App\Services\AuctionItemService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuctionItemController extends Controller
{
    public function __construct(private readonly AuctionItemService $items) {}

    public function index(Auction $auction): Response
    {
        return Inertia::render('admin/auctions/items', [
            'auction' => [
                'id' => $auction->id,
                'slug' => $auction->slug,
                'title' => $auction->title,
                'type' => $auction->type,
                'status' => $auction->status,
                'starts_at' => $auction->starts_at->format('d M Y, H:i'),
            ],
            'items' => $this->items->forAuction($auction)
                ->map(fn (AuctionItem $item) => [
                    'id' => $item->id,
                    'position' => $item->position,
                    'name' => $item->product->name,
                    'thumb' => $this->thumbUrl($item->product),
                    'starting_price' => $item->starting_price,
                    'status' => $item->status,
                ])
                ->values()
                ->all(),
            'availableProducts' => $this->items->availableProducts()
                ->map(fn (Product $product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                ])
                ->values()
                ->all(),
        ]);
    }

    public function store(AuctionItemRequest $request, Auction $auction): RedirectResponse
    {
        $product = Product::findOrFail((int) $request->validated('product_id'));

        $this->items->attach($auction, $product, (string) $request->validated('starting_price'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item added to auction.')]);

        return back();
    }

    public function move(Request $request, Auction $auction, AuctionItem $item): RedirectResponse
    {
        abort_unless($item->auction_id === $auction->id, 404);

        $this->items->move($item, $request->input('direction') === 'up' ? -1 : 1);

        return back();
    }

    public function destroy(Auction $auction, AuctionItem $item): RedirectResponse
    {
        abort_unless($item->auction_id === $auction->id, 404);

        $this->items->detach($item);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Item removed from auction.')]);

        return back();
    }

    private function thumbUrl(Product $product): ?string
    {
        $url = $product->getFirstMediaUrl(Product::IMAGES, 'thumb');

        return $url === '' ? null : $url;
    }
}
