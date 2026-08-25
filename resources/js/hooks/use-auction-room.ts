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

type RoomState = {
    current: RoomItem | null;
    items: RoomItem[];
    bids: RoomBid[];
};

/**
 * Subscribes to an auction's channel and folds incoming events into room state.
 *
 * Every event carries the item's resulting state, so the reducer never derives a
 * status locally — the server remains the only authority on what an item is doing.
 */
export const useAuctionRoom = (auctionId: number, initial: RoomState) => {
    const [current, setCurrent] = useState(initial.current);
    const [items, setItems] = useState(initial.items);
    const [bids, setBids] = useState(initial.bids);
    const [seed, setSeed] = useState(initial);

    if (seed !== initial) {
        setSeed(initial);
        setCurrent(initial.current);
        setItems(initial.items);
        setBids(initial.bids);
    }

    const patchCurrent = (patch: ItemPatch) => {
        setCurrent((existing) =>
            existing && existing.id === patch.id
                ? { ...existing, ...patch }
                : existing,
        );

        setItems((existing) =>
            existing.map((item) =>
                item.id === patch.id ? { ...item, ...patch } : item,
            ),
        );
    };

    useEchoPublic<BidPlacedPayload>(
        `auction.${auctionId}`,
        '.bid.placed',
        (payload) => {
            setBids((existing) => [payload.bid, ...existing].slice(0, 30));
            patchCurrent(payload.item);
        },
    );

    useEchoPublic<ItemPayload>(
        `auction.${auctionId}`,
        '.item.activated',
        (payload) => {
            setBids([]);
            setCurrent(payload.item as RoomItem);
            setItems((existing) =>
                existing.map((item) =>
                    item.id === payload.item.id
                        ? { ...item, ...payload.item }
                        : item,
                ),
            );
        },
    );

    useEchoPublic<ItemPayload>(
        `auction.${auctionId}`,
        '.countdown.started',
        (payload) => patchCurrent(payload.item),
    );

    useEchoPublic<ItemPayload>(
        `auction.${auctionId}`,
        '.item.sold',
        (payload) => patchCurrent(payload.item),
    );

    return { current, items, bids };
};
