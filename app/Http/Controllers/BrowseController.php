<?php

namespace App\Http\Controllers;

use App\Models\AuctionItem;
use App\Services\AuctionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrowseController extends Controller
{
    /**
     * Page sizes offered by the "Items per Page" control.
     *
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [12, 30, 60];

    public function __construct(private AuctionService $auctions) {}

    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'in:'.implode(',', self::PER_PAGE_OPTIONS)],
        ]);

        return Inertia::render('browse', [
            'items' => $this->auctions->paginateItemsForStorefront(
                $filters['search'] ?? null,
                $filters['per_page'] ?? self::PER_PAGE_OPTIONS[1],
            )->through(fn (AuctionItem $item) => $this->auctions->toStorefrontItem($item)),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'per_page' => $filters['per_page'] ?? self::PER_PAGE_OPTIONS[1],
            ],
            'perPageOptions' => self::PER_PAGE_OPTIONS,
        ]);
    }
}
