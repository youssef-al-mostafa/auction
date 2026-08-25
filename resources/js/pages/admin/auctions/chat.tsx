import { Head, usePage } from '@inertiajs/react';
import { ConsoleChat } from '@/components/app/console-chat';
import { index as auctionsIndex } from '@/routes/admin/auctions';
import type { ChatConversation, RoomAuction } from '@/types';

type AdminChatProps = {
    auction: RoomAuction;
    conversations: ChatConversation[];
};

const AdminChat = ({ auction, conversations }: AdminChatProps) => {
    const { auth } = usePage().props;

    return (
        <>
            <Head title={`Chat — ${auction.title}`} />

            <div className="flex flex-col gap-6 p-4">
                <div className="space-y-1">
                    <h1 className="text-2xl font-semibold tracking-tight">
                        Chat
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Direct messages from bidders in {auction.title}.
                    </p>
                </div>

                <div className="max-w-3xl">
                    <ConsoleChat
                        auctionId={auction.id}
                        conversations={conversations}
                        currentUserId={auth.user?.id ?? null}
                    />
                </div>
            </div>
        </>
    );
};

AdminChat.layout = {
    breadcrumbs: [
        {
            title: 'Auctions',
            href: auctionsIndex(),
        },
    ],
};

export default AdminChat;
