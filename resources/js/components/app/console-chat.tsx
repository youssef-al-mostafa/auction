import { ChevronLeft } from 'lucide-react';
import { useState } from 'react';
import { ChatWindow } from '@/components/app/chat-window';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Item,
    ItemContent,
    ItemDescription,
    ItemTitle,
} from '@/components/ui/item';
import { ScrollArea } from '@/components/ui/scroll-area';
import { useAuctionChat } from '@/hooks/use-auction-chat';
import { formatTime } from '@/lib/datetime';
import { store as sendAdminChatMessage } from '@/routes/admin/chat/messages';
import type { ChatConversation } from '@/types';

type ConsoleChatProps = {
    auctionId: number;
    conversations: ChatConversation[];
    currentUserId: number | null;
    className?: string;
};

export const ConsoleChat = ({
    auctionId,
    conversations: initialConversations,
    currentUserId,
    className,
}: ConsoleChatProps) => {
    const conversations = useAuctionChat(auctionId, initialConversations);
    const [openThreadId, setOpenThreadId] = useState<number | null>(null);

    const open = conversations.find(
        (conversation) => conversation.id === openThreadId,
    );

    if (open) {
        return (
            <div className="space-y-2">
                <Button
                    variant="ghost"
                    size="sm"
                    onClick={() => setOpenThreadId(null)}
                >
                    <ChevronLeft />
                    All conversations
                </Button>

                <ChatWindow
                    title={open.bidder ?? 'Bidder'}
                    subtitle={`${open.messages_count} messages`}
                    messages={open.messages}
                    currentUserId={currentUserId}
                    action={sendAdminChatMessage.form(open.id)}
                    emptyMessage="No messages in this thread yet."
                    className={className}
                />
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <h3 className="border-b border-sidebar-border/40 p-4 font-medium">
                Conversations
            </h3>

            {conversations.length === 0 ? (
                <p className="p-6 text-center text-sm text-muted-foreground">
                    No bidder has messaged you yet.
                </p>
            ) : (
                <ScrollArea className="h-96">
                    <div className="p-2">
                        {conversations.map((conversation) => (
                            <button
                                key={conversation.id}
                                type="button"
                                onClick={() => setOpenThreadId(conversation.id)}
                                className="w-full text-left"
                            >
                                <Item size="sm" className="rounded-lg">
                                    <ItemContent>
                                        <ItemTitle>
                                            {conversation.bidder ?? 'Unknown'}
                                            <Badge variant="secondary">
                                                {conversation.messages_count}
                                            </Badge>
                                        </ItemTitle>
                                        <ItemDescription>
                                            {conversation.last_message ??
                                                'No messages'}
                                        </ItemDescription>
                                    </ItemContent>
                                    <span className="text-xs text-muted-foreground">
                                        {formatTime(
                                            conversation.last_message_at,
                                        )}
                                    </span>
                                </Item>
                            </button>
                        ))}
                    </div>
                </ScrollArea>
            )}
        </div>
    );
};
