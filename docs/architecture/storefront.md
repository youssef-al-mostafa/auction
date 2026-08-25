# Public Storefront

The unauthenticated surface — what a visitor sees before logging in. Laid out from the mockups in
`resources/Auction home work.docx`, which are a storefront rather than a dashboard.

## Routes

| Route | Page | Layout |
|---|---|---|
| `GET /` (`home`) | `pages/home.tsx` | `PublicLayout` |
| `GET /browse` (`browse`) | `pages/browse.tsx` | `PublicLayout` |

`app.tsx` resolves `home` and `browse` to `PublicLayout`; everything else keeps the sidebar app
layout.

## Pages

**Home** — hero, a "Live auctions" rail of running and scheduled live auctions with a countdown to
start, and an "Open for bidding" grid drawn from running auctions.

**Browse** — the card grid from `image3`: search, items-per-page (12/30/60), a
comfortable/compact density toggle, pagination, and an empty state.

## Layout and Components

```
layouts/public-layout.tsx
layouts/public/public-header.tsx
layouts/public/public-footer.tsx
components/app/auction-item-card.tsx
components/app/auction-status-pill.tsx
hooks/use-countdown.ts
lib/money.ts
lib/placeholder-image.ts
```

`placeholder-image.ts` renders a deterministic gradient tile when a product has no media, so the
grid never shows a broken image. It resolves itself as products get real photos.

`money.ts` formats decimal strings the way the mockups do — `120$`, `5$` — dropping trailing zero
cents.

## Data Flow

`AuctionService` serves both the admin panel and the storefront:

| Method | Returns | Used by |
|---|---|---|
| `liveAndUpcoming()` | Running + scheduled live auctions | Home rail |
| `featuredItems()` | Flattened items from running auctions | Home grid |
| `paginateItemsForStorefront()` | Paginated `AuctionItem` models | Browse |
| `toStorefrontItem()` | One item flattened with its product | Both controllers |

Services return **models**; controllers do the presentation mapping:

```php
'items' => $this->auctions->paginateItemsForStorefront($search, $perPage)
    ->through(fn (AuctionItem $item) => $this->auctions->toStorefrontItem($item)),
```

Keeping `->through()` in the controller rather than the service also avoids a PHPStan invariance
error — `LengthAwarePaginator`'s `TValue` template is not covariant, so a service method cannot
claim to return a paginator of a different type than the one it built.

## Known Gaps

| Where | Waiting on |
|---|---|
| `toStorefrontItem()` → `current_bid` | The `bids` table. Cards fall back to `starting_price` while it is `null`. |
| `auction-item-card.tsx` → `auctionUrl()` | The public auction detail route. The `BID NOW` href is hand-built until it exists. |
| `pages/home.tsx` → "Enter room" link | Same. |

`pages/welcome.tsx` is orphaned — `/` renders `home` now. Safe to delete.

There is deliberately **no categories feature**. The mockups show a category strip, but categories
appear nowhere in the brief and have no table behind them.
