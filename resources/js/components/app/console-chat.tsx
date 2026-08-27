import { ChatPanel } from '@/components/app/chat-window';
import { store as sendAdminChatMessage } from '@/routes/admin/auctions/chat';
import type { RoomChat } from '@/types';

type ConsoleChatProps = {
    auctionId: number;
    auctionSlug: string;
    chat: RoomChat;
    currentUserId: number | null;
    className?: string;
};

/**
 * The admin's view of the room chat — the same single conversation bidders see,
 * posted into as the auctioneer.
 */
export const ConsoleChat = ({
    auctionId,
    auctionSlug,
    chat,
    currentUserId,
    className,
}: ConsoleChatProps) => (
    <ChatPanel
        auctionId={auctionId}
        title="Room chat"
        subtitle="Everyone in this auction sees these messages"
        messages={chat.messages}
        currentUserId={currentUserId}
        action={sendAdminChatMessage.form(auctionSlug)}
        emptyMessage="No messages in this room yet."
        className={className}
    />
);
