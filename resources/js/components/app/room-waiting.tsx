import { CalendarClock, Gavel, PartyPopper } from 'lucide-react';
import { LotList } from '@/components/app/lot-list';
import { useCountdown } from '@/hooks/use-countdown';
import { formatDeadline } from '@/lib/datetime';
import type { RoomAuction, RoomItem } from '@/types';

type RoomWaitingProps = {
    auction: RoomAuction;
    items: RoomItem[];
};

const copyFor = (auction: RoomAuction, pending: number) => {
    if (auction.status === 'ended') {
        return {
            icon: PartyPopper,
            title: 'This auction has ended',
            body: 'Every lot has gone under the hammer. The results are below.',
        };
    }

    if (auction.status === 'running') {
        return {
            icon: Gavel,
            title: 'Between lots',
            body:
                pending > 0
                    ? `The auctioneer is about to launch the next lot, with ${pending} still to come. This page updates on its own, no need to refresh.`
                    : 'The auctioneer is wrapping up. This page updates on its own, no need to refresh.',
        };
    }

    return {
        icon: CalendarClock,
        title: 'The auction has not started yet',
        body: 'Bidding opens when the auctioneer starts the sale. Keep this page open — it updates on its own.',
    };
};

export const RoomWaiting = ({ auction, items }: RoomWaitingProps) => {
    const pending = items.filter((item) => item.status === 'pending').length;
    const untilStart = useCountdown(
        auction.status === 'scheduled' ? auction.starts_at : null,
    );

    const { icon: Icon, title, body } = copyFor(auction, pending);
    const showCountdown = auction.status === 'scheduled' && !untilStart.expired;

    return (
        <div className="space-y-4 lg:col-span-3">
            <div className="rounded-xl border border-sidebar-border/70 p-10 text-center dark:border-sidebar-border">
                <Icon className="mx-auto mb-4 size-10 text-muted-foreground" />

                <h2 className="text-lg font-semibold">{title}</h2>

                <p className="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                    {body}
                </p>

                {auction.status === 'scheduled' && (
                    <p className="mt-4 text-sm text-muted-foreground">
                        Starts {formatDeadline(auction.starts_at)}
                    </p>
                )}

                {showCountdown && (
                    <p className="mt-1 font-mono text-3xl font-semibold tabular-nums">
                        {untilStart.hours}:{untilStart.minutes}:
                        {untilStart.seconds}
                    </p>
                )}
            </div>

            {items.length > 0 && (
                <LotList
                    items={items}
                    showResults={auction.status === 'ended'}
                />
            )}
        </div>
    );
};
