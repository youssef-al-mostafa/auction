<?php

namespace App\Services;

use App\Enums\PaymentStatusEnum;
use App\Exceptions\PaymentRejectedException;
use App\Models\AuctionItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    /**
     * @return Collection<int, AuctionItem>
     */
    public function wonItemsFor(User $user): Collection
    {
        return $user->wonItems()
            ->with(['product.media', 'auction'])
            ->latest('closed_at')
            ->latest('id')
            ->get();
    }

    /**
     * The win a user still owes money on, so the celebration survives a missed
     * broadcast or a reload rather than depending on catching the event live.
     *
     * @return array<string, mixed>|null
     */
    public function pendingWinFor(?User $user): ?array
    {
        if (! $user instanceof User) {
            return null;
        }

        $item = $user->wonItems()
            ->whereNull('paid_at')
            ->where('payment_deadline', '>', now())
            ->with(['product.media', 'auction'])
            ->latest('closed_at')
            ->first();

        return $item instanceof AuctionItem ? $this->toWonItem($item) : null;
    }

    /**
     * @throws PaymentRejectedException
     */
    public function pay(AuctionItem $item, User $user): AuctionItem
    {
        return DB::transaction(function () use ($item, $user): AuctionItem {
            $locked = AuctionItem::query()
                ->whereKey($item->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->winner_id !== $user->id) {
                throw PaymentRejectedException::notTheWinner();
            }

            match ($locked->paymentStatus()) {
                PaymentStatusEnum::PENDING => null,
                PaymentStatusEnum::PAID => throw PaymentRejectedException::alreadyPaid(),
                PaymentStatusEnum::EXPIRED => throw PaymentRejectedException::deadlinePassed(),
                null => throw PaymentRejectedException::notWon(),
            };

            $locked->update(['paid_at' => now()]);

            return $locked;
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function toWonItem(AuctionItem $item): array
    {
        $image = $item->product->getFirstMediaUrl(Product::IMAGES, 'small');

        return [
            'id' => $item->id,
            'name' => $item->product->name,
            'image' => $image === '' ? null : $image,
            'auction_title' => $item->auction->title,
            'sold_price' => $item->sold_price,
            'closed_at' => $item->closed_at?->toIso8601String(),
            'payment_deadline' => $item->payment_deadline?->toIso8601String(),
            'paid_at' => $item->paid_at?->toIso8601String(),
            'payment_status' => $item->paymentStatus()?->value,
        ];
    }
}
