import { Link } from '@inertiajs/react';
import { AuctionStatusPill } from '@/components/app/auction-status-pill';
import { Button } from '@/components/ui/button';
import { formatMoney } from '@/lib/money';
import { placeholderImage } from '@/lib/placeholder-image';
import { cn } from '@/lib/utils';
import { room } from '@/routes/auctions';
import { show as showItem } from '@/routes/items';
import type { StorefrontItem } from '@/types';

/**
 * A live lot is only bid on from the room the auctioneer runs; an ongoing
 * auction opens every one of its lots at once, so each gets its own page.
 */
const auctionUrl = (item: StorefrontItem) =>
    item.auction.type === 'live' ? room(item.auction.slug) : showItem(item.id);

type AuctionItemCardProps = {
    item: StorefrontItem;
    className?: string;
};

export const AuctionItemCard = ({ item, className }: AuctionItemCardProps) => {
    const closed = item.status === 'sold' || item.status === 'unsold';

    return (
        <article
            className={cn(
                'group flex flex-col overflow-hidden rounded-xl border bg-card transition hover:shadow-lg',
                className,
            )}
        >
            <div className="aspect-square overflow-hidden bg-muted">
                <img
                    src={item.image ?? placeholderImage(item.name)}
                    alt={item.name}
                    loading="lazy"
                    className="size-full object-cover transition duration-300 group-hover:scale-105"
                />
            </div>

            <div className="flex flex-1 flex-col gap-3 p-4">
                <h3 className="line-clamp-2 text-sm leading-snug font-semibold">
                    {item.name}
                </h3>

                <AuctionStatusPill
                    status={item.status}
                    className="self-start"
                />

                <div className="flex items-center gap-2 border-b pb-3 text-xs text-muted-foreground">
                    <span>Starting Bid</span>
                    <span className="font-semibold text-foreground">
                        {formatMoney(item.starting_price)}
                    </span>
                </div>

                <div className="flex items-center justify-between">
                    <span className="text-sm font-medium">Current Bid</span>
                    <span className="text-xl font-bold text-primary">
                        {formatMoney(item.current_bid ?? item.starting_price)}
                    </span>
                </div>

                <Button
                    asChild={!closed}
                    disabled={closed}
                    className="mt-auto w-full font-semibold tracking-wide"
                >
                    {closed ? (
                        <span>
                            {item.status === 'sold' ? 'SOLD' : 'UNSOLD'}
                        </span>
                    ) : (
                        <Link href={auctionUrl(item)}>BID NOW</Link>
                    )}
                </Button>
            </div>
        </article>
    );
};
