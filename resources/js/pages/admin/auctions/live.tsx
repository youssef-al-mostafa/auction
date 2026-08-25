import { Form, Head, usePage } from '@inertiajs/react';
import { Gavel, Play, Power, Square, Timer } from 'lucide-react';
import { BidFeed } from '@/components/app/bid-feed';
import { ConsoleChat } from '@/components/app/console-chat';
import { LotList } from '@/components/app/lot-list';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { COUNTDOWN_PRESETS } from '@/config/auction';
import { useAuctionRoom } from '@/hooks/use-auction-room';
import { useCountdown } from '@/hooks/use-countdown';
import { formatMoney } from '@/lib/money';
import {
    index as auctionsIndex,
    end as endAuction,
    launchNext,
    start as startAuction,
} from '@/routes/admin/auctions';
import { close, countdown } from '@/routes/admin/auctions/items';
import type { ChatConversation, RoomAuction, RoomBid, RoomItem } from '@/types';

type LiveConsoleProps = {
    auction: RoomAuction;
    current: RoomItem | null;
    items: RoomItem[];
    bids: RoomBid[];
    conversations: ChatConversation[];
};

const LiveConsole = ({
    auction,
    current: initialCurrent,
    items: initialItems,
    bids: initialBids,
    conversations,
}: LiveConsoleProps) => {
    const { auth } = usePage().props;
    const { current, items, bids } = useAuctionRoom(auction.id, {
        current: initialCurrent,
        items: initialItems,
        bids: initialBids,
    });

    const threadCount = conversations.length;

    const remaining = useCountdown(current?.countdown_ends_at ?? null);
    const isCountingDown = current?.status === 'counting_down';
    const isClosed = current?.status === 'sold' || current?.status === 'unsold';
    const hasUpcoming = items.some((item) => item.status === 'pending');
    const isRunning = auction.status === 'running';

    return (
        <>
            <Head title={`Live — ${auction.title}`} />

            <div className="space-y-6 p-4">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        variant="small"
                        title={auction.title}
                        description="Live auctioneer console"
                    />

                    <div className="flex items-center gap-3">
                        <Badge
                            variant={isRunning ? 'default' : 'secondary'}
                            className="capitalize"
                        >
                            {auction.status}
                        </Badge>

                        {isRunning ? (
                            <Form
                                {...endAuction.form(auction.slug)}
                                options={{ preserveScroll: true }}
                            >
                                {({ processing }) => (
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={processing}
                                    >
                                        <Square />
                                        End auction
                                    </Button>
                                )}
                            </Form>
                        ) : (
                            auction.status !== 'ended' && (
                                <Form
                                    {...startAuction.form(auction.slug)}
                                    options={{ preserveScroll: true }}
                                >
                                    {({ processing }) => (
                                        <Button size="sm" disabled={processing}>
                                            <Power />
                                            Start auction
                                        </Button>
                                    )}
                                </Form>
                            )
                        )}
                    </div>
                </div>

                {!isRunning && (
                    <div className="rounded-xl border border-dashed border-sidebar-border/70 p-4 text-sm text-muted-foreground dark:border-sidebar-border">
                        {auction.status === 'ended'
                            ? 'This auction has ended.'
                            : 'Start the auction to put the first lot under the hammer.'}
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <div className="rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
                            {current ? (
                                <div className="space-y-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Lot {current.position} · under
                                                the hammer
                                            </p>
                                            <h2 className="text-xl font-semibold">
                                                {current.name}
                                            </h2>
                                        </div>

                                        <Badge
                                            variant={
                                                isCountingDown
                                                    ? 'destructive'
                                                    : 'default'
                                            }
                                            className="capitalize"
                                        >
                                            {current.status.replace('_', ' ')}
                                        </Badge>
                                    </div>

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Current bid
                                            </p>
                                            <p className="text-3xl font-semibold">
                                                {current.current_bid
                                                    ? formatMoney(
                                                          current.current_bid,
                                                      )
                                                    : '—'}
                                            </p>
                                            <p className="text-sm text-muted-foreground">
                                                {current.current_bidder ??
                                                    `Starting at ${formatMoney(current.starting_price)}`}
                                            </p>
                                        </div>

                                        <div>
                                            <p className="text-sm text-muted-foreground">
                                                Countdown
                                            </p>
                                            <p
                                                className={`font-mono text-3xl font-semibold ${
                                                    isCountingDown
                                                        ? 'text-destructive'
                                                        : 'text-muted-foreground'
                                                }`}
                                            >
                                                {isCountingDown
                                                    ? `${remaining.minutes}:${remaining.seconds}`
                                                    : '--:--'}
                                            </p>
                                        </div>
                                    </div>

                                    {isClosed ? (
                                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-sidebar-border/40 pt-4">
                                            <p className="text-sm">
                                                {current.status === 'sold'
                                                    ? `Sold to ${current.winner_name} for ${formatMoney(current.sold_price)}`
                                                    : 'Closed with no bids.'}
                                            </p>

                                            <Form
                                                {...launchNext.form(
                                                    auction.slug,
                                                )}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        disabled={
                                                            processing ||
                                                            !hasUpcoming ||
                                                            !isRunning
                                                        }
                                                    >
                                                        <Play />
                                                        Launch next lot
                                                    </Button>
                                                )}
                                            </Form>
                                        </div>
                                    ) : (
                                        <div className="flex flex-wrap gap-2 border-t border-sidebar-border/40 pt-4">
                                            {COUNTDOWN_PRESETS.map(
                                                (seconds) => (
                                                    <Form
                                                        key={seconds}
                                                        {...countdown.form([
                                                            auction.slug,
                                                            current.id,
                                                        ])}
                                                        options={{
                                                            preserveScroll: true,
                                                        }}
                                                    >
                                                        {({ processing }) => (
                                                            <>
                                                                <input
                                                                    type="hidden"
                                                                    name="seconds"
                                                                    value={
                                                                        seconds
                                                                    }
                                                                />
                                                                <Button
                                                                    variant="outline"
                                                                    size="sm"
                                                                    disabled={
                                                                        processing
                                                                    }
                                                                >
                                                                    <Timer />
                                                                    {seconds}s
                                                                </Button>
                                                            </>
                                                        )}
                                                    </Form>
                                                ),
                                            )}

                                            <Form
                                                {...close.form([
                                                    auction.slug,
                                                    current.id,
                                                ])}
                                                options={{
                                                    preserveScroll: true,
                                                }}
                                            >
                                                {({ processing }) => (
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        disabled={processing}
                                                    >
                                                        <Gavel />
                                                        Close &amp; award
                                                    </Button>
                                                )}
                                            </Form>
                                        </div>
                                    )}
                                </div>
                            ) : (
                                <div className="space-y-4 text-center">
                                    <p className="text-muted-foreground">
                                        Nothing under the hammer.
                                    </p>

                                    <Form
                                        {...launchNext.form(auction.slug)}
                                        options={{ preserveScroll: true }}
                                    >
                                        {({ processing }) => (
                                            <Button
                                                disabled={
                                                    processing ||
                                                    !hasUpcoming ||
                                                    !isRunning
                                                }
                                            >
                                                <Play />
                                                Launch next lot
                                            </Button>
                                        )}
                                    </Form>
                                </div>
                            )}
                        </div>

                        <LotList
                            items={items}
                            currentId={current?.id ?? null}
                            showResults
                        />
                    </div>

                    <Tabs defaultValue="bids">
                        <TabsList className="w-full">
                            <TabsTrigger value="bids" className="flex-1">
                                Bid feed
                            </TabsTrigger>
                            <TabsTrigger value="chat" className="flex-1">
                                Chat
                                {threadCount > 0 && (
                                    <Badge variant="secondary">
                                        {threadCount}
                                    </Badge>
                                )}
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="bids">
                            <BidFeed bids={bids} />
                        </TabsContent>

                        <TabsContent value="chat">
                            <ConsoleChat
                                auctionId={auction.id}
                                conversations={conversations}
                                currentUserId={auth.user?.id ?? null}
                                className="h-[calc(100dvh-22rem)]"
                            />
                        </TabsContent>
                    </Tabs>
                </div>
            </div>
        </>
    );
};

LiveConsole.layout = ({ auction }: LiveConsoleProps) => ({
    breadcrumbs: [
        { title: 'Auctions', href: auctionsIndex() },
        { title: auction.title, href: '#' },
    ],
});

export default LiveConsole;
