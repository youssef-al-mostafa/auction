import { formatMoney } from '@/lib/money';
import type { RoomBid } from '@/types';

type BidFeedProps = {
    bids: RoomBid[];
    title?: string;
    highlightUserId?: number | null;
    emptyMessage?: string;
};

export const BidFeed = ({
    bids,
    title = 'Bid feed',
    highlightUserId = null,
    emptyMessage = 'No bids on this lot yet.',
}: BidFeedProps) => (
    <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
        <h3 className="border-b border-sidebar-border/40 p-4 font-medium">
            {title}
        </h3>

        {bids.length === 0 ? (
            <p className="p-6 text-center text-sm text-muted-foreground">
                {emptyMessage}
            </p>
        ) : (
            <ul className="divide-y divide-sidebar-border/40">
                {bids.map((bid) => (
                    <li
                        key={bid.id}
                        className="flex justify-between p-3 text-sm"
                    >
                        <span>
                            {bid.bidder_id === highlightUserId
                                ? 'You'
                                : bid.bidder}
                        </span>
                        <span className="font-medium">
                            {formatMoney(bid.amount)}
                        </span>
                    </li>
                ))}
            </ul>
        )}
    </div>
);
