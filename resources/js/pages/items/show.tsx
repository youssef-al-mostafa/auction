import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Radio } from 'lucide-react';
import { AuctionItemCard } from '@/components/app/auction-item-card';
import { AuctionStatusPill } from '@/components/app/auction-status-pill';
import { BidFeed } from '@/components/app/bid-feed';
import { BidPanel } from '@/components/app/bid-panel';
import { Button } from '@/components/ui/button';
import { useAuctionItem } from '@/hooks/use-auction-item';
import { formatMoney } from '@/lib/money';
import { placeholderImage } from '@/lib/placeholder-image';
import { browse } from '@/routes';
import { room } from '@/routes/auctions';
import type {
    ItemPageAuction,
    RoomBid,
    RoomItem,
    StorefrontItem,
} from '@/types';

type ItemShowProps = {
    auction: ItemPageAuction;
    item: RoomItem;
    bids: RoomBid[];
    otherItems: StorefrontItem[];
};

const ItemShow = ({
    auction,
    item: initialItem,
    bids: initialBids,
    otherItems,
}: ItemShowProps) => {
    const { auth } = usePage().props;
    const { item, bids } = useAuctionItem(auction.id, {
        item: initialItem,
        bids: initialBids,
    });

    const isLive = auction.type === 'live';

    return (
        <>
            <Head title={item.name} />

            <div className="mx-auto max-w-7xl space-y-10 px-4 py-10">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={browse()}>
                            <ArrowLeft />
                            All lots
                        </Link>
                    </Button>

                    <p className="text-sm text-muted-foreground">
                        Lot {item.position} of {auction.title}
                    </p>
                </div>

                <div className="grid gap-8 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <div className="overflow-hidden rounded-xl border bg-card">
                            <img
                                src={item.image ?? placeholderImage(item.name)}
                                alt={item.name}
                                className="aspect-4/3 w-full object-cover"
                            />
                        </div>

                        <div className="space-y-3">
                            <AuctionStatusPill status={item.status} />

                            <h1 className="text-3xl font-bold tracking-tight">
                                {item.name}
                            </h1>

                            {item.description && (
                                <p className="leading-relaxed text-muted-foreground">
                                    {item.description}
                                </p>
                            )}
                        </div>

                        <BidFeed
                            bids={bids}
                            title={`Bid history (${bids.length})`}
                            highlightUserId={auth.user?.id ?? null}
                            emptyMessage="No bids on this lot yet. Be the first."
                        />
                    </div>

                    <div className="space-y-4">
                        {isLive ? (
                            <div className="space-y-4 rounded-xl border p-6">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        {item.current_bid
                                            ? 'Current bid'
                                            : 'Starting bid'}
                                    </p>
                                    <p className="text-4xl font-semibold">
                                        {formatMoney(
                                            item.current_bid ??
                                                item.starting_price,
                                        )}
                                    </p>
                                </div>

                                <p className="text-sm text-muted-foreground">
                                    This lot is sold by an auctioneer. Bidding
                                    happens in the live room, where the
                                    countdown runs.
                                </p>

                                <Button className="w-full" asChild>
                                    <Link href={room(auction.slug)}>
                                        <Radio />
                                        Enter the live room
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <BidPanel
                                item={item}
                                currentUserId={auth.user?.id ?? null}
                                closesAt={auction.ends_at}
                            />
                        )}

                        <div className="space-y-2 rounded-xl border p-6 text-sm">
                            <p className="font-medium">{auction.title}</p>
                            <p className="text-muted-foreground">
                                {isLive
                                    ? 'Live auction — lots go under the hammer one at a time.'
                                    : 'Ongoing auction — every lot takes bids until it closes.'}
                            </p>
                        </div>
                    </div>
                </div>

                {otherItems.length > 0 && (
                    <section className="space-y-5">
                        <h2 className="text-2xl font-bold tracking-tight">
                            More from this auction
                        </h2>

                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            {otherItems.map((other) => (
                                <AuctionItemCard key={other.id} item={other} />
                            ))}
                        </div>
                    </section>
                )}
            </div>
        </>
    );
};

export default ItemShow;
