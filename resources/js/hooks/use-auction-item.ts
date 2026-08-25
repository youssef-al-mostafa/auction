import { useEchoPublic } from '@laravel/echo-react';
import { useState } from 'react';
import type { RoomBid, RoomItem } from '@/types';

type ItemPatch = Partial<RoomItem> & { id: number };

type BidPlacedPayload = {
    bid: RoomBid;
    item: ItemPatch;
};

type ItemPayload = {
    item: ItemPatch;
};

type ItemState = {
    item: RoomItem;
    bids: RoomBid[];
};

/**
 * Watches one lot on its auction's public channel. The channel carries every lot
 * in the auction, so each event is matched against this item before it lands —
 * a bid on lot 3 must not move the price shown on lot 1.
 */
export const useAuctionItem = (auctionId: number, initial: ItemState) => {
    const [item, setItem] = useState(initial.item);
    const [bids, setBids] = useState(initial.bids);
    const [seed, setSeed] = useState(initial);

    if (seed !== initial) {
        setSeed(initial);
        setItem(initial.item);
        setBids(initial.bids);
    }

    const patch = (incoming: ItemPatch) => {
        setItem((existing) =>
            existing.id === incoming.id
                ? { ...existing, ...incoming }
                : existing,
        );
    };

    useEchoPublic<BidPlacedPayload>(
        `auction.${auctionId}`,
        '.bid.placed',
        (payload) => {
            if (payload.item.id !== initial.item.id) {
                return;
            }

            setBids((existing) => [payload.bid, ...existing]);
            patch({ ...payload.item, current_bidder: payload.bid.bidder });
        },
    );

    useEchoPublic<ItemPayload>(
        `auction.${auctionId}`,
        '.countdown.started',
        (payload) => patch(payload.item),
    );

    useEchoPublic<ItemPayload>(
        `auction.${auctionId}`,
        '.item.sold',
        (payload) => patch(payload.item),
    );

    return { item, bids };
};
