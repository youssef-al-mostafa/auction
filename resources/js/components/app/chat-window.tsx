import { Form, Link } from '@inertiajs/react';
import { SendHorizontal } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { CHAT_MESSAGE_MAX_LENGTH } from '@/config/chat';
import { useChatThread } from '@/hooks/use-chat-thread';
import { formatTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';
import { login } from '@/routes';
import type { ChatMessage } from '@/types';

type ChatFormAction = {
    action: string;
    method: 'post';
};

type ChatWindowProps = {
    title: string;
    subtitle?: string;
    messages: ChatMessage[];
    currentUserId: number | null;
    action: ChatFormAction | null;
    emptyMessage: string;
    className?: string;
};

type ChatMessageFormData = {
    body: string;
};

const MessageBubble = ({
    message,
    mine,
}: {
    message: ChatMessage;
    mine: boolean;
}) => (
    <div className={cn('flex flex-col gap-1', mine && 'items-end')}>
        <span className="px-1 text-xs text-muted-foreground">
            {mine ? 'You' : message.author} · {formatTime(message.sent_at)}
        </span>
        <p
            className={cn(
                'max-w-[85%] rounded-2xl px-3 py-2 text-sm break-words whitespace-pre-wrap',
                mine
                    ? 'rounded-br-sm bg-primary text-primary-foreground'
                    : 'rounded-bl-sm bg-muted text-foreground',
            )}
        >
            {message.body}
        </p>
    </div>
);

export const ChatWindow = ({
    title,
    subtitle,
    messages,
    currentUserId,
    action,
    emptyMessage,
    className,
}: ChatWindowProps) => {
    const bottom = useRef<HTMLDivElement>(null);

    useEffect(() => {
        bottom.current?.scrollIntoView({ block: 'end' });
    }, [messages.length]);

    return (
        <div
            className={cn(
                'flex h-[calc(100dvh-16rem)] min-h-80 flex-col overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border',
                className,
            )}
        >
            <div className="border-b border-sidebar-border/40 px-4 py-3">
                <h3 className="font-medium">{title}</h3>
                {subtitle && (
                    <p className="text-xs text-muted-foreground">{subtitle}</p>
                )}
            </div>

            <ScrollArea className="min-h-0 flex-1">
                {messages.length === 0 ? (
                    <p className="p-6 text-center text-sm text-muted-foreground">
                        {emptyMessage}
                    </p>
                ) : (
                    <div className="flex flex-col gap-4 p-4">
                        {messages.map((message) => (
                            <MessageBubble
                                key={message.id}
                                message={message}
                                mine={message.author_id === currentUserId}
                            />
                        ))}
                        <div ref={bottom} />
                    </div>
                )}
            </ScrollArea>

            <div className="border-t border-sidebar-border/40 p-3">
                {action === null ? (
                    <Button asChild variant="outline" className="w-full">
                        <Link href={login()}>Log in to chat</Link>
                    </Button>
                ) : (
                    <Form<ChatMessageFormData>
                        {...action}
                        options={{ preserveScroll: true }}
                        resetOnSuccess
                        className="flex items-center gap-2"
                    >
                        {({ processing }) => (
                            <>
                                <Input
                                    name="body"
                                    required
                                    autoComplete="off"
                                    maxLength={CHAT_MESSAGE_MAX_LENGTH}
                                    placeholder="Type your message here…"
                                />
                                <Button
                                    size="icon"
                                    disabled={processing}
                                    aria-label="Send message"
                                >
                                    <SendHorizontal />
                                </Button>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </div>
    );
};

const SubscribedChatWindow = ({
    threadId,
    messages,
    ...rest
}: ChatWindowProps & { threadId: number }) => {
    const live = useChatThread(threadId, messages);

    return <ChatWindow {...rest} messages={live} />;
};

type ChatPanelProps = ChatWindowProps & {
    threadId: number | null;
};

export const ChatPanel = ({ threadId, ...rest }: ChatPanelProps) =>
    threadId === null ? (
        <ChatWindow {...rest} />
    ) : (
        <SubscribedChatWindow threadId={threadId} {...rest} />
    );
