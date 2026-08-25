<?php

namespace Database\Seeders;

use App\Enums\AuctionItemStatusEnum;
use App\Enums\AuctionStatusEnum;
use App\Enums\AuctionTypeEnum;
use App\Models\Auction;
use App\Models\AuctionItem;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Catalogue for the public storefront — one running live auction, one scheduled
 * live auction, and two ongoing auctions.
 */
class AuctionsSeeder extends Seeder
{
    /**
     * @var list<int>
     */
    private const STARTING_PRICES = [25, 40, 75, 120, 250, 480, 900, 1500];

    /**
     * @var list<array{name: string, description: string}>
     */
    private const PRODUCTS = [
        [
            'name' => 'Laptop Dell Vostro 3400 Intel Core i3 - 256GB',
            'description' => 'Everyday workhorse laptop in excellent condition. 8GB RAM, 256GB NVMe SSD, 14" FHD display. Battery holds roughly 6 hours. Charger included, no visible scuffs on the lid.',
        ],
        [
            'name' => 'Samsung Galaxy S20 FE 8GB/256GB Blue',
            'description' => 'Unlocked and factory reset. Screen is flawless with no burn-in. Light wear on the frame corners. Original box, cable, and SIM tool included.',
        ],
        [
            'name' => 'Samsung Galaxy Z Fold 3 512GB Phantom Green',
            'description' => 'Folding flagship with the crease barely visible. Inner display protector replaced last month. Includes case and fast charger.',
        ],
        [
            'name' => 'Apple Watch Series 7 45mm Nike Sport Loop',
            'description' => 'GPS model with the orange and blue Nike loop. Always-on display, no scratches on the glass. Health sensors fully functional.',
        ],
        [
            'name' => 'Vintage Omega Seamaster De Ville 1968',
            'description' => 'Automatic movement, recently serviced by a certified watchmaker. Original dial with even patina. Runs within +4 seconds a day.',
        ],
        [
            'name' => 'Fender Stratocaster American Standard 1968',
            'description' => 'Sunburst finish with genuine road wear. Original pickups, rewired harness. Plays beautifully with low action. Hard case included.',
        ],
        [
            'name' => 'Leica M6 Rangefinder Camera Body',
            'description' => 'Classic 35mm rangefinder. Light meter accurate, shutter speeds tested across the range. Brass showing through on the edges.',
        ],
        [
            'name' => 'Sony WH-1000XM5 Wireless Headphones',
            'description' => 'Industry-leading noise cancellation. Earpads recently replaced. Includes carry case, cable, and airline adapter.',
        ],
        [
            'name' => 'iPad Pro 12.9" M2 256GB with Magic Keyboard',
            'description' => 'Liquid Retina XDR display, cellular model. Battery health above 90%. Magic Keyboard shows minor shine on the palm rest.',
        ],
        [
            'name' => 'Persian Tabriz Hand-Knotted Rug 2x3m',
            'description' => 'Wool on cotton foundation, roughly 300 knots per square inch. Deep indigo field with an ivory medallion. Professionally cleaned.',
        ],
        [
            'name' => 'Montblanc Meisterstück 149 Fountain Pen',
            'description' => 'Piston filler with an 18k gold medium nib. Writes wet and smooth. Resin body free of cracks, includes service box.',
        ],
        [
            'name' => 'LG C2 42" 4K Smart OLED evo TV',
            'description' => 'Best all-round OLED we have tested. Gaming-oriented feature set with 120Hz and low input lag. Wall mount and remote included.',
        ],
        [
            'name' => 'Nintendo Switch OLED Zelda Edition',
            'description' => 'Limited edition console, joy-cons show no drift. Dock, cables, and the original packaging all present.',
        ],
        [
            'name' => 'Herman Miller Aeron Chair Size B Graphite',
            'description' => 'Remastered model with posturefit SL. Mesh taut with no sagging. Gas lift and tilt mechanism both smooth.',
        ],
    ];

    public function run(): void
    {
        $products = collect(self::PRODUCTS)->map(
            fn (array $attributes): Product => Product::firstOrCreate(
                ['name' => $attributes['name']],
                ['description' => $attributes['description']],
            ),
        );

        $this->liveRunning($products->take(5));
        $this->liveScheduled($products->slice(5, 4));
        $this->ongoing($products->slice(9, 3), 'Collectors Vault — Watches & Instruments', 1);
        $this->ongoing($products->slice(12, 2), 'Home & Studio Clearance', 4);
    }

    /**
     * The live room demo: item two is on the block, item one already sold.
     *
     * @param  Collection<int, Product>  $products
     */
    private function liveRunning(Collection $products): void
    {
        $auction = $this->auction('Live Collectors Night', AuctionTypeEnum::LIVE, [
            'status' => AuctionStatusEnum::RUNNING,
            'starts_at' => Carbon::now()->subMinutes(20),
            'ends_at' => null,
        ]);

        $statuses = [
            AuctionItemStatusEnum::SOLD,
            AuctionItemStatusEnum::ACTIVE,
            AuctionItemStatusEnum::PENDING,
            AuctionItemStatusEnum::PENDING,
            AuctionItemStatusEnum::PENDING,
        ];

        $products->values()->each(function (Product $product, int $index) use ($auction, $statuses): void {
            $this->item($auction, $product, $index, $statuses[$index]);
        });
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function liveScheduled(Collection $products): void
    {
        $auction = $this->auction('November Watch Sale', AuctionTypeEnum::LIVE, [
            'status' => AuctionStatusEnum::SCHEDULED,
            'starts_at' => Carbon::now()->addHours(6),
            'ends_at' => null,
        ]);

        $products->values()->each(function (Product $product, int $index) use ($auction): void {
            $this->item($auction, $product, $index, AuctionItemStatusEnum::PENDING);
        });
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    private function ongoing(Collection $products, string $title, int $startedDaysAgo): void
    {
        $auction = $this->auction($title, AuctionTypeEnum::ONGOING, [
            'status' => AuctionStatusEnum::RUNNING,
            'starts_at' => Carbon::now()->subDays($startedDaysAgo),
            'ends_at' => Carbon::now()->addDays(7 - $startedDaysAgo),
        ]);

        $products->values()->each(function (Product $product, int $index) use ($auction): void {
            $this->item($auction, $product, $index, AuctionItemStatusEnum::ACTIVE);
        });
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function auction(string $title, AuctionTypeEnum $type, array $attributes): Auction
    {
        return Auction::firstOrCreate(
            ['title' => $title],
            [...$attributes, 'type' => $type, 'slug' => Str::slug($title)],
        );
    }

    private function item(Auction $auction, Product $product, int $index, AuctionItemStatusEnum $status): void
    {
        AuctionItem::firstOrCreate(
            ['auction_id' => $auction->id, 'product_id' => $product->id],
            [
                'position' => $index + 1,
                'starting_price' => self::STARTING_PRICES[$index % count(self::STARTING_PRICES)],
                'status' => $status,
            ],
        );
    }
}
