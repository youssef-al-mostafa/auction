import { Head, usePage } from '@inertiajs/react';
import { BidFeed } from '@/components/app/bid-feed';
import { BidPanel } from '@/components/app/bid-panel';
import { ChatPanel } from '@/components/app/chat-window';
import { LotList } from '@/components/app/lot-list';
import { Badge } from '@/components/ui/badge';
import { useAuctionRoom } from '@/hooks/use-auction-room';
import { store as sendChatMessage } from '@/routes/auctions/chat';
import type { RoomAuction, RoomBid, RoomChat, RoomItem } from '@/types';

type RoomProps = {
    auction: RoomAuction;
    current: RoomItem | null;
    items: RoomItem[];
    bids: RoomBid[];
    chat: RoomChat;
};

const Room = ({
    auction,
    current: initialCurrent,
    items: initialItems,
    bids: initialBids,
    chat,
}: RoomProps) => {
    const { auth } = usePage().props;
    const { current, items, bids } = useAuctionRoom(auction.id, {
        current: initialCurrent,
        items: initialItems,
        bids: initialBids,
    });

    const userId = auth.user?.id ?? null;

    return (
        <>
            <Head title={auction.title} />

            <div className="mx-auto max-w-6xl space-y-6 p-4">
                <div className="flex items-center justify-between gap-4">
                    <h1 className="text-2xl font-semibold">{auction.title}</h1>
                    <Badge variant="secondary" className="capitalize">
                        {auction.status}
                    </Badge>
                </div>

                <div className="grid gap-6 lg:grid-cols-4">
                    <ChatPanel
                        threadId={chat.thread_id}
                        title="Chat"
                        subtitle="Talk directly with the auction admin"
                        messages={chat.messages}
                        currentUserId={userId}
                        action={
                            userId === null
                                ? null
                                : sendChatMessage.form(auction.slug)
                        }
                        emptyMessage="No messages yet. Say hello to the admin."
                    />

                    {!current ? (
                        <div className="rounded-xl border border-sidebar-border/70 p-12 text-center lg:col-span-3 dark:border-sidebar-border">
                            <p className="text-muted-foreground">
                                No lot is under the hammer right now. This page
                                updates the moment one is.
                            </p>
                        </div>
                    ) : (
                        <>
                            <div className="space-y-4 lg:col-span-2">
                                <div className="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                                    {current.image ? (
                                        <img
                                            src={current.image}
                                            alt={current.name}
                                            className="aspect-video w-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex aspect-video items-center justify-center bg-muted text-muted-foreground">
                                            No image
                                        </div>
                                    )}

                                    <div className="space-y-2 p-6">
                                        <p className="text-sm text-muted-foreground">
                                            Lot {current.position}
                                        </p>
                                        <h2 className="text-xl font-semibold">
                                            {current.name}
                                        </h2>
                                        {current.description && (
                                            <p className="text-sm text-muted-foreground">
                                                {current.description}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <BidFeed
                                    bids={bids}
                                    title="Bid history"
                                    highlightUserId={userId}
                                    emptyMessage="No bids yet. Be the first."
                                />
                            </div>

                            <div className="space-y-4">
                                <BidPanel
                                    key={current.id}
                                    item={current}
                                    currentUserId={userId}
                                />
                                <LotList items={items} currentId={current.id} />
                            </div>
                        </>
                    )}
                </div>
            </div>
        </>
    );
};

export default Room;
