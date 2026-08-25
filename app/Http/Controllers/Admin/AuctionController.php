<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AuctionStatusEnum;
use App\Enums\AuctionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\AuctionRequest;
use App\Models\Auction;
use App\Services\AuctionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AuctionController extends Controller
{
    public function __construct(private readonly AuctionService $auctions) {}

    public function index(): Response
    {
        $auctions = $this->auctions->paginateForAdmin()
            ->through(fn (Auction $auction) => [
                'id' => $auction->id,
                'slug' => $auction->slug,
                'title' => $auction->title,
                'type' => $auction->type,
                'status' => $auction->status,
                'starts_at' => $auction->starts_at->format('d M Y, H:i'),
                'ends_at' => $auction->ends_at?->format('d M Y, H:i'),
                'items_count' => $auction->auction_items_count,
            ]);

        return Inertia::render('admin/auctions/index', [
            'auctions' => $auctions,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/auctions/create', $this->formOptions());
    }

    public function store(AuctionRequest $request): RedirectResponse
    {
        $this->auctions->create($request->auctionAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Auction created.')]);

        return to_route('admin.auctions.index');
    }

    public function edit(Auction $auction): Response
    {
        return Inertia::render('admin/auctions/edit', [
            ...$this->formOptions(),
            'auction' => [
                'id' => $auction->id,
                'slug' => $auction->slug,
                'title' => $auction->title,
                'type' => $auction->type,
                'status' => $auction->status,
                'start_date' => $auction->starts_at->format('Y-m-d'),
                'start_time' => $auction->starts_at->format('H:i'),
                'end_date' => $auction->ends_at?->format('Y-m-d'),
                'end_time' => $auction->ends_at?->format('H:i'),
            ],
        ]);
    }

    public function update(AuctionRequest $request, Auction $auction): RedirectResponse
    {
        $this->auctions->update($auction, $request->auctionAttributes());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Auction updated.')]);

        return to_route('admin.auctions.index');
    }

    public function destroy(Auction $auction): RedirectResponse
    {
        $this->auctions->delete($auction);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Auction deleted.')]);

        return to_route('admin.auctions.index');
    }

    /**
     * @return array<string, list<string>>
     */
    private function formOptions(): array
    {
        return [
            'types' => AuctionTypeEnum::values(),
            'statuses' => AuctionStatusEnum::values(),
        ];
    }
}
