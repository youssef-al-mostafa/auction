import { useEcho } from '@laravel/echo-react';
import { useState } from 'react';
import type { ChatMessage } from '@/types';

type ChatMessagePayload = {
    message: ChatMessage;
    server_time: string;
};

export const useChatThread = (
    threadId: number,
    initial: ChatMessage[],
): ChatMessage[] => {
    const [messages, setMessages] = useState(initial);
    const [seed, setSeed] = useState(initial);

    if (seed !== initial) {
        setSeed(initial);
        setMessages(initial);
    }

    useEcho<ChatMessagePayload>(
        `chat.thread.${threadId}`,
        '.chat.message',
        (payload) =>
            setMessages((existing) =>
                existing.some((message) => message.id === payload.message.id)
                    ? existing
                    : [...existing, payload.message],
            ),
        [threadId],
    );

    return messages;
};
