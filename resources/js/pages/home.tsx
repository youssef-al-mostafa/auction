import { Head, Link } from '@inertiajs/react';
import { ArrowRight, Radio } from 'lucide-react';
import { AuctionItemCard } from '@/components/app/auction-item-card';
import { Button } from '@/components/ui/button';
import { useCountdown } from '@/hooks/use-countdown';
import { browse } from '@/routes';
import { room } from '@/routes/auctions';
import type { Auction, StorefrontItem } from '@/types';

type HomeProps = {
    liveAuctions: Auction[];
    featuredItems: StorefrontItem[];
};

const LiveAuctionCard = ({ auction }: { auction: Auction }) => {
    const running = auction.status === 'running';
    const remaining = useCountdown(running ? null : auction.starts_at);

    return (
        <article className="flex flex-col gap-3 rounded-xl border bg-card p-5">
            <div className="flex items-center justify-between gap-3">
                <span
                    className={
                        running
                            ? 'inline-flex items-center gap-1.5 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-700 dark:bg-rose-950 dark:text-rose-300'
                            : 'inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                    }
                >
                    <Radio className="size-3" />
                    {running ? 'On air now' : 'Scheduled'}
                </span>

                <span className="text-xs text-muted-foreground">
                    {auction.auction_items_count ?? 0} items
                </span>
            </div>

            <h3 className="text-base leading-snug font-semibold">
                {auction.title}
            </h3>

            <div className="mt-auto flex items-end justify-between gap-3">
                <div>
                    <p className="text-xs text-muted-foreground">
                        {running ? 'Started' : 'Starts in'}
                    </p>
                    <p className="font-mono text-lg font-bold text-primary">
                        {running
                            ? new Date(auction.starts_at).toLocaleTimeString(
                                  [],
                                  { hour: '2-digit', minute: '2-digit' },
                              )
                            : `${remaining.hours}:${remaining.minutes}:${remaining.seconds}`}
                    </p>
                </div>

                <Button size="sm" variant="secondary" asChild>
                    <Link href={room(auction.slug)}>
                        Enter room
                        <ArrowRight />
                    </Link>
                </Button>
            </div>
        </article>
    );
};

const Home = ({ liveAuctions, featuredItems }: HomeProps) => (
    <>
        <Head title="Live & ongoing auctions" />

        <section className="border-b bg-primary text-primary-foreground">
            <div className="mx-auto max-w-7xl px-4 py-16 sm:py-24">
                <h1 className="max-w-2xl text-4xl font-bold tracking-tight sm:text-5xl">
                    Bid live, or take your time.
                </h1>

                <p className="mt-4 max-w-xl text-primary-foreground/80">
                    Join an auctioneer-run live room where a late bid resets the
                    countdown, or place your bid on an ongoing lot whenever it
                    suits you.
                </p>

                <div className="mt-8 flex flex-wrap gap-3">
                    <Button size="lg" variant="secondary" asChild>
                        <Link href={browse()}>Browse all lots</Link>
                    </Button>
                </div>
            </div>
        </section>

        <div className="mx-auto max-w-7xl space-y-16 px-4 py-14">
            <section className="space-y-5">
                <div className="flex items-end justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">
                            Live auctions
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Auctioneer-run rooms, on air now or opening shortly
                        </p>
                    </div>
                </div>

                {liveAuctions.length === 0 ? (
                    <p className="rounded-xl border border-dashed py-12 text-center text-sm text-muted-foreground">
                        No live auctions scheduled right now. Check back soon.
                    </p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {liveAuctions.map((auction) => (
                            <LiveAuctionCard
                                key={auction.id}
                                auction={auction}
                            />
                        ))}
                    </div>
                )}
            </section>

            <section className="space-y-5">
                <div className="flex items-end justify-between gap-4">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">
                            Open for bidding
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            Lots you can bid on right now
                        </p>
                    </div>

                    <Button variant="ghost" asChild>
                        <Link href={browse()}>
                            View all
                            <ArrowRight />
                        </Link>
                    </Button>
                </div>

                {featuredItems.length === 0 ? (
                    <p className="rounded-xl border border-dashed py-12 text-center text-sm text-muted-foreground">
                        Nothing is open for bidding yet.
                    </p>
                ) : (
                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {featuredItems.map((item) => (
                            <AuctionItemCard key={item.id} item={item} />
                        ))}
                    </div>
                )}
            </section>
        </div>
    </>
);

export default Home;
