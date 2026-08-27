<?php

use App\Enums\PermissionsEnum;
use App\Http\Controllers\Admin\AuctionController;
use App\Http\Controllers\Admin\AuctionItemController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\LiveAuctionController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\AuctionRoomController;
use App\Http\Controllers\BidController;
use App\Http\Controllers\BrowseController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItemController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('browse', [BrowseController::class, 'index'])->name('browse');

Route::get('auctions/{auction}', [AuctionRoomController::class, 'show'])->name('auctions.room');
Route::get('items/{item}', [ItemController::class, 'show'])->name('items.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::post('items/{item}/bids', [BidController::class, 'store'])
        ->name('items.bids.store');

    Route::get('won-items', [CheckoutController::class, 'index'])
        ->name('won-items.index');
    Route::post('won-items/{item}/pay', [CheckoutController::class, 'pay'])
        ->name('won-items.pay');

    Route::post('auctions/{auction}/chat', [ChatController::class, 'store'])
        ->name('auctions.chat.store');
});

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::middleware('permission:'.PermissionsEnum::MANAGE_PRODUCTS->value)
            ->resource('products', ProductController::class)
            ->except('show');

        Route::middleware('permission:'.PermissionsEnum::MANAGE_AUCTIONS->value)
            ->group(function () {
                Route::resource('auctions', AuctionController::class)->except('show');

                Route::get('auctions/{auction}/items', [AuctionItemController::class, 'index'])
                    ->name('auctions.items.index');
                Route::post('auctions/{auction}/items', [AuctionItemController::class, 'store'])
                    ->name('auctions.items.store');
                Route::patch('auctions/{auction}/items/{item}/move', [AuctionItemController::class, 'move'])
                    ->name('auctions.items.move');
                Route::delete('auctions/{auction}/items/{item}', [AuctionItemController::class, 'destroy'])
                    ->name('auctions.items.destroy');

                Route::get('auctions/{auction}/live', [LiveAuctionController::class, 'console'])
                    ->name('auctions.live');
                Route::post('auctions/{auction}/start', [LiveAuctionController::class, 'start'])
                    ->name('auctions.start');
                Route::post('auctions/{auction}/end', [LiveAuctionController::class, 'end'])
                    ->name('auctions.end');
                Route::post('auctions/{auction}/launch-next', [LiveAuctionController::class, 'launchNext'])
                    ->name('auctions.launch-next');
                Route::post('auctions/{auction}/items/{item}/countdown', [LiveAuctionController::class, 'startCountdown'])
                    ->name('auctions.items.countdown');
                Route::post('auctions/{auction}/items/{item}/close', [LiveAuctionController::class, 'close'])
                    ->name('auctions.items.close');

                Route::get('auctions/{auction}/chat', [AdminChatController::class, 'index'])
                    ->name('auctions.chat.index');
                Route::post('auctions/{auction}/chat', [AdminChatController::class, 'store'])
                    ->name('auctions.chat.store');
            });
    });

require __DIR__.'/settings.php';
