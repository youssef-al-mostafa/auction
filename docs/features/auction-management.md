# Auction Management

The admin side of setting up an auction, all behind `permission:manage-auctions`.

## Routes

| Method | URI | Action |
|---|---|---|
| GET | `/admin/auctions` | List |
| GET | `/admin/auctions/create` | Create form |
| POST | `/admin/auctions` | Store |
| GET | `/admin/auctions/{auction}/edit` | Edit form |
| PUT | `/admin/auctions/{auction}` | Update |
| DELETE | `/admin/auctions/{auction}` | Delete |
| GET | `/admin/auctions/{auction}/items` | Item manager |
| POST | `/admin/auctions/{auction}/items` | Attach a product |
| PATCH | `/admin/auctions/{auction}/items/{item}/move` | Reorder |
| DELETE | `/admin/auctions/{auction}/items/{item}` | Detach |

Auctions bind by **slug** (`getRouteKeyName()`), so URLs read
`/admin/auctions/november-watch-sale/items`.

## Date and Time

The database stores one `starts_at` timestamp. The form presents a **date picker and a time
picker** as separate inputs, validated separately so errors attach to the right field, then
collapsed on save:

```php
public function auctionAttributes(): array
{
    $validated = $this->validated();

    return [
        // …
        'starts_at' => Carbon::parse("{$validated['start_date']} {$validated['start_time']}"),
        'ends_at' => $this->endsAt($validated),
    ];
}
```

`end_date` / `end_time` are `required_if:type,ongoing` — an ongoing auction expires, a live auction
is closed manually by the admin.

**Dates are formatted server-side for display.** `APP_TIMEZONE` is UTC, so `starts_at` serialises
with a `Z` suffix; formatting it in the browser with `toLocaleString()` would shift an admin's
`14:30` to whatever their local zone makes of it. The controller sends preformatted strings for
display and separate `start_date` / `start_time` for the form, so both agree with what was typed.

## Attaching Items

A product may sit in only **one auction that has not ended**. Enforced as validation rather than a
database constraint, so a product can be relisted once its auction ends — which is the reason
products and auction items are separate tables in the first place.

```php
if (! app(AuctionItemService::class)->isProductAvailable($product)) {
    $fail('This product is already in an auction that has not ended.');
}
```

The product dropdown only offers free products, so validation is a backstop rather than the primary
guard.

## Launch Order

The brief puts *"item ordering"* under admin control. `auction_items.position` holds it, and the
item manager exposes up/down controls. `AuctionItemService::move()` swaps positions inside a
transaction, then renormalises the auction's items to `1..n` — necessary because seeded rows can
share a position, which would otherwise make a swap a no-op.

`AuctionItem::inLaunchOrder()` orders by `position` then `id`.

## Services

Business logic sits in services; controllers stay thin.

| Service | Responsibility |
|---|---|
| `AuctionService` | Auction CRUD, plus the storefront queries |
| `AuctionItemService` | Attach, detach, reorder, availability |
| `ProductService` | Product CRUD and media attach/remove |

Controllers inject via constructor promotion and map results to view payloads.

## Deleting

Deleting an auction **cascades** to its items; the products survive. Deleting a product that sits in
any auction is **blocked** — `ProductService::isDeletable()` checks first so the admin gets a toast
rather than a database constraint error.
