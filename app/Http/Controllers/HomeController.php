<?php

namespace App\Http\Controllers;

use App\Services\AuctionService;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private AuctionService $auctions) {}

    public function index(): Response
    {
        return Inertia::render('home', [
            'liveAuctions' => $this->auctions->liveAndUpcoming(),
            'featuredItems' => $this->auctions->featuredItems(),
        ]);
    }
}
