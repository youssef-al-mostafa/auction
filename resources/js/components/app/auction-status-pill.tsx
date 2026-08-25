import { cn } from '@/lib/utils';
import type { AuctionItemStatus, AuctionStatus } from '@/types';

const itemLabels: Record<AuctionItemStatus, string> = {
    pending: 'Auction Starting',
    active: 'Auction Started',
    counting_down: 'Going Once',
    sold: 'Sold',
    unsold: 'Unsold',
};

const itemStyles: Record<AuctionItemStatus, string> = {
    pending: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    counting_down:
        'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    sold: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
    unsold: 'bg-muted text-muted-foreground',
};

const auctionLabels: Record<AuctionStatus, string> = {
    draft: 'Draft',
    scheduled: 'Scheduled',
    running: 'Live Now',
    ended: 'Ended',
};

export const AuctionStatusPill = ({
    status,
    className,
}: {
    status: AuctionItemStatus;
    className?: string;
}) => (
    <span
        className={cn(
            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
            itemStyles[status],
            className,
        )}
    >
        <span className="size-1.5 rounded-full bg-current" />
        {itemLabels[status]}
    </span>
);

export const auctionStatusLabel = (status: AuctionStatus): string =>
    auctionLabels[status];
