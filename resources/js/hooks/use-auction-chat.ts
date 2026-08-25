import { useEcho } from '@laravel/echo-react';
import { useState } from 'react';
import type { ChatConversation, ChatMessage } from '@/types';

type ChatMessagePayload = {
    message: ChatMessage;
    server_time: string;
};

const opened = (message: ChatMessage): ChatConversation => ({
    id: message.thread_id,
    bidder: message.author,
    bidder_id: message.author_id,
    messages_count: 1,
    last_message: message.body,
    last_message_at: message.sent_at,
    messages: [message],
});

const fold = (
    conversations: ChatConversation[],
    message: ChatMessage,
): ChatConversation[] => {
    const known = conversations.some(
        (conversation) => conversation.id === message.thread_id,
    );

    if (!known) {
        return message.from_admin
            ? conversations
            : [opened(message), ...conversations];
    }

    return conversations.map((conversation) => {
        if (conversation.id !== message.thread_id) {
            return conversation;
        }

        if (conversation.messages.some((held) => held.id === message.id)) {
            return conversation;
        }

        return {
            ...conversation,
            messages: [...conversation.messages, message],
            messages_count: conversation.messages_count + 1,
            last_message: message.body,
            last_message_at: message.sent_at,
        };
    });
};

export const useAuctionChat = (
    auctionId: number,
    initial: ChatConversation[],
): ChatConversation[] => {
    const [conversations, setConversations] = useState(initial);
    const [seed, setSeed] = useState(initial);

    if (seed !== initial) {
        setSeed(initial);
        setConversations(initial);
    }

    useEcho<ChatMessagePayload>(
        `chat.auction.${auctionId}`,
        '.chat.message',
        (payload) =>
            setConversations((existing) => fold(existing, payload.message)),
        [auctionId],
    );

    return conversations;
};
