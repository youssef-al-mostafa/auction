# Auction Data Model

## Three Separate Tables

The domain deliberately keeps **products**, **auctions**, and **auction items** apart rather than
merging the item into the auction:

1. **Products** — the thing itself
   - Example: "Vintage Omega Seamaster", "1968 Fender Stratocaster"
   - Exists independently of any auction, and can be relisted

2. **Auctions** — the event
   - Example: "November Watch Sale" (ongoing), "Live Collectors Night" (live)
   - Carries type, status, and when it starts

3. **Auction Items** — the join between the two
   - Ties one product to one auction
   - Owns the `starting_price` and the per-item lifecycle status

The split is what allows the same product to be listed in a later auction at a different starting
price, and it gives bids a stable row to attach to without touching the product record.

## Database Implementation

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->timestamps();
});

Schema::create('auctions', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('slug')->unique();
    $table->string('type');
    $table->string('status')->default(AuctionStatusEnum::DRAFT->value);
    $table->timestamp('starts_at');
    $table->timestamp('ends_at')->nullable();
    $table->timestamps();

    $table->index(['status', 'starts_at']);
});

Schema::create('auction_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->restrictOnDelete();
    $table->unsignedInteger('position')->default(0);
    $table->decimal('starting_price', 12, 2);
    $table->string('status')->default(AuctionItemStatusEnum::PENDING->value);
    $table->timestamps();

    $table->unique(['auction_id', 'product_id']);
    $table->index(['auction_id', 'position']);
});
```

## Entity Relationships

- **Products** have many Auction Items
- **Products** have many Media *(polymorphic, via spatie/laravel-medialibrary)*
- **Auctions** have many Auction Items
- **Auction Items** belong to one Product and one Auction
- **Auction Items** will have many Bids *(not yet implemented)*

## Product Images

Images live on the **product**, not the auction item — a product relisted in a later auction keeps
its photos rather than needing re-upload.

Storage is `spatie/laravel-medialibrary`. The `media` table is polymorphic, so `products` holds no
image column at all; each media row points back at its product. That avoids orphaned ids, gives
ordering via `order_column`, and cascades cleanly when a product is deleted.

```php
class Product extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const IMAGES = 'images';

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection(self::IMAGES)
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/avif']);
    }
}
```

| Conversion | Width | Used by |
|---|---|---|
| `thumb` | 100px | Admin tables, item manager |
| `small` | 480px | Storefront cards, admin edit form |
| `large` | 1200px | Item detail |

Conversions are **`nonQueued()`** deliberately. MediaLibrary queues them by default through
`QUEUE_CONNECTION`, which is `database` here — so anyone running plain `php artisan serve` with no
worker would get no thumbnails at all. Synchronous generation costs a moment on upload and always
works.

Requires `MEDIA_DISK=public` and `php artisan storage:link`.

## Design Decisions

### Enum columns are stored as `string`

`type` and `status` are plain `string` columns cast to PHP enums on the model, rather than
`$table->enum()`.

On PostgreSQL, `$table->enum()` produces a `varchar` with a `CHECK` constraint. Adding a new status
later then means dropping and recreating that constraint inside a migration. With a string column
the enum class is the single source of truth, and Laravel still rejects unknown values at the cast
layer.

```php
protected function casts(): array
{
    return [
        'type' => AuctionTypeEnum::class,
        'status' => AuctionStatusEnum::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
```

### Delete rules differ per relationship

| Action | Behaviour | Reason |
|--------|-----------|--------|
| Delete an auction | Cascades to its auction items | The link has no meaning without the event |
| Delete a product in an auction | **Blocked** (`restrictOnDelete`) | The item may carry bids and a winner |

`ProductController::destroy()` checks `auctionItems()->exists()` first so the admin gets a readable
message instead of a database constraint error.

### One `starts_at` timestamp, two form inputs

The database stores a single `starts_at` timestamp. The admin form presents a separate date picker
and time picker, combined server-side on save. Storing one timestamp keeps comparison and ordering
trivial for the auction engine.

### Money is `decimal(12, 2)`

`starting_price` maps to PostgreSQL `numeric(12,2)` — exact, no floating-point drift, up to
9,999,999,999.99. It is cast as `decimal:2`, so it stays a string in PHP rather than becoming a
float.

### Slugs are generated on the model

`Auction` sets its slug in a `creating` hook rather than in the controller, so the unique index
cannot be violated from a seeder, a test, or tinker either. Collisions get a numeric suffix.

```php
protected static function booted(): void
{
    static::creating(function (self $auction): void {
        $auction->slug ??= self::uniqueSlug($auction->title);
    });
}
```

`getRouteKeyName()` returns `slug`, so auction URLs read `/auctions/november-watch-sale`.

## Enums

| Enum | Values |
|------|--------|
| `AuctionTypeEnum` | `ongoing`, `live` |
| `AuctionStatusEnum` | `draft`, `scheduled`, `running`, `ended` |
| `AuctionItemStatusEnum` | `pending`, `active`, `counting_down`, `sold`, `unsold` |

`AuctionItemStatusEnum` is the column the live auction state machine turns on. Module 1 only ever
writes `pending`.

## Query Scopes

Filtering lives on the models rather than in controllers:

```php
Auction::live();                  // type = live
Auction::ongoing();               // type = ongoing
AuctionItem::inLaunchOrder();     // position, then id
```
