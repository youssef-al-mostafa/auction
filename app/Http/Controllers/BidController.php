<?php

namespace App\Http\Controllers;

use App\Exceptions\BidRejectedException;
use App\Http\Requests\BidRequest;
use App\Models\AuctionItem;
use App\Services\BiddingService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class BidController extends Controller
{
    public function __construct(private readonly BiddingService $bidding) {}

    public function store(BidRequest $request, AuctionItem $item): RedirectResponse
    {
        try {
            $this->bidding->place(
                $item,
                $request->user(),
                (string) $request->validated('amount'),
                (string) $request->validated('idempotency_key'),
            );
        } catch (BidRejectedException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back();
    }
}
