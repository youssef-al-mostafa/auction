import { useEchoPublic } from '@laravel/echo-react';
import { useState } from 'react';
import type { ChatMessage } from '@/types';

type ChatMessagePayload = {
    message: ChatMessage;
    server_time: string;
};

/**
 * Subscribes to an auction's room-wide chat.
 *
 * The channel is keyed on the auction rather than the thread so the room can
 * listen before anyone has posted, when no thread row exists yet. The sender
 * receives its own broadcast alongside the redirect that reseeds `initial`,
 * so messages are folded in by id.
 */
export const useAuctionChat = (
    auctionId: number,
    initial: ChatMessage[],
): ChatMessage[] => {
    const [messages, setMessages] = useState(initial);
    const [seed, setSeed] = useState(initial);

    if (seed !== initial) {
        setSeed(initial);
        setMessages(initial);
    }

    useEchoPublic<ChatMessagePayload>(
        `chat.auction.${auctionId}`,
        '.chat.message',
        (payload) =>
            setMessages((existing) =>
                existing.some((message) => message.id === payload.message.id)
                    ? existing
                    : [...existing, payload.message],
            ),
    );

    return messages;
};
