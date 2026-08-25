<?php

namespace App\Http\Controllers;

use App\Exceptions\PaymentRejectedException;
use App\Models\AuctionItem;
use App\Services\CheckoutService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout) {}

    public function index(Request $request): Response
    {
        return Inertia::render('won-items', [
            'items' => $this->checkout->wonItemsFor($request->user())
                ->map(fn (AuctionItem $item) => $this->checkout->toWonItem($item))
                ->values()
                ->all(),
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    public function pay(Request $request, AuctionItem $item): RedirectResponse
    {
        try {
            $this->checkout->pay($item, $request->user());

            Inertia::flash('toast', [
                'type' => 'success',
                'message' => __('Payment complete. This item is yours.'),
            ]);
        } catch (PaymentRejectedException $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => $e->getMessage()]);
        }

        return back();
    }
}
