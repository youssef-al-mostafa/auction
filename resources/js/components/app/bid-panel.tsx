import { Form, Link } from '@inertiajs/react';
import { Gavel } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { QUICK_BID_INCREMENTS } from '@/config/auction';
import { useCountdown } from '@/hooks/use-countdown';
import { formatDeadline } from '@/lib/datetime';
import { formatMoney, raiseBy } from '@/lib/money';
import { login } from '@/routes';
import { store as placeBid } from '@/routes/items/bids';
import type { RoomItem } from '@/types';

type BidFormData = {
    amount: string;
    idempotency_key: string;
};

type BidPanelProps = {
    item: RoomItem;
    currentUserId: number | null;
    /** An ongoing auction has no auctioneer — its lots close on this timestamp. */
    closesAt?: string | null;
};

/** Below a day, an absolute deadline reads worse than a ticking clock. */
const CLOCK_THRESHOLD_SECONDS = 86400;

export const BidPanel = ({
    item,
    currentUserId,
    closesAt = null,
}: BidPanelProps) => {
    const [amount, setAmount] = useState('');
    const remaining = useCountdown(item.countdown_ends_at);
    const untilClose = useCountdown(closesAt);

    const floor = item.current_bid ?? item.starting_price;
    const isCountingDown = item.status === 'counting_down';
    const hasClosed = closesAt !== null && untilClose.expired;
    const isOpen = (item.status === 'active' || isCountingDown) && !hasClosed;
    const isTopBidder =
        item.current_bidder_id != null &&
        item.current_bidder_id === currentUserId;

    return (
        <div className="space-y-4 rounded-xl border border-sidebar-border/70 p-6 dark:border-sidebar-border">
            <div>
                <p className="text-sm text-muted-foreground">
                    {item.current_bid ? 'Current bid' : 'Starting bid'}
                </p>
                <p className="text-4xl font-semibold">{formatMoney(floor)}</p>
                {item.current_bidder && (
                    <p className="text-sm text-muted-foreground">
                        {isTopBidder
                            ? 'You are the highest bidder'
                            : `Held by ${item.current_bidder}`}
                    </p>
                )}
            </div>

            {closesAt !== null && item.status === 'active' && (
                <div className="flex items-center justify-between rounded-lg bg-muted p-3 text-sm">
                    <span className="text-muted-foreground">
                        {hasClosed ? 'Bidding closed' : 'Bidding closes'}
                    </span>
                    <span className="font-medium tabular-nums">
                        {!hasClosed &&
                        untilClose.totalSeconds < CLOCK_THRESHOLD_SECONDS
                            ? `${untilClose.hours}:${untilClose.minutes}:${untilClose.seconds}`
                            : formatDeadline(closesAt)}
                    </span>
                </div>
            )}

            {isCountingDown && (
                <div className="rounded-lg bg-destructive/10 p-4 text-center">
                    <p className="text-sm text-destructive">Going once…</p>
                    <p className="font-mono text-4xl font-semibold text-destructive">
                        {remaining.minutes}:{remaining.seconds}
                    </p>
                </div>
            )}

            {item.status === 'sold' && (
                <p className="text-center font-medium">
                    Sold to {item.winner_name} for{' '}
                    {formatMoney(item.sold_price)}
                </p>
            )}

            {item.status === 'unsold' && (
                <p className="text-center text-muted-foreground">
                    Closed with no bids.
                </p>
            )}

            {!isOpen ? null : currentUserId === null ? (
                <Button asChild className="w-full">
                    <Link href={login()}>Log in to bid</Link>
                </Button>
            ) : (
                <Form<BidFormData>
                    {...placeBid.form(item.id)}
                    options={{ preserveScroll: true }}
                    transform={(data) => ({
                        ...data,
                        idempotency_key: crypto.randomUUID(),
                    })}
                    className="space-y-3"
                >
                    {({ processing }) => (
                        <>
                            <div className="flex flex-wrap gap-2">
                                {QUICK_BID_INCREMENTS.map((increment) => (
                                    <Button
                                        key={increment}
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setAmount(raiseBy(floor, increment))
                                        }
                                    >
                                        +{increment}$
                                    </Button>
                                ))}
                            </div>

                            <Input
                                name="amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                required
                                value={amount}
                                onChange={(event) =>
                                    setAmount(event.target.value)
                                }
                                placeholder={raiseBy(floor, 1)}
                            />

                            <Button className="w-full" disabled={processing}>
                                <Gavel />
                                Place bid
                            </Button>
                        </>
                    )}
                </Form>
            )}
        </div>
    );
};
