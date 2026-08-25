<?php

namespace App\Services;

use App\Events\ChatMessageSent;
use App\Models\Auction;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    public function threadFor(Auction $auction, User $user): ChatThread
    {
        return ChatThread::firstOrCreate([
            'auction_id' => $auction->id,
            'user_id' => $user->id,
        ]);
    }

    public function existingThreadFor(Auction $auction, User $user): ?ChatThread
    {
        return ChatThread::query()
            ->where('auction_id', $auction->id)
            ->where('user_id', $user->id)
            ->first();
    }

    public function post(ChatThread $thread, User $user, string $body): ChatMessage
    {
        $message = $thread->messages()->create([
            'user_id' => $user->id,
            'body' => $body,
        ]);

        $thread->touch();

        ChatMessageSent::dispatch($message->load('user'));

        return $message;
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function messagesFor(?ChatThread $thread, int $limit = 100): Collection
    {
        if (! $thread instanceof ChatThread) {
            return new Collection;
        }

        return $thread->messages()
            ->with('user')
            ->inSendOrder()
            ->take($limit)
            ->get();
    }

    /**
     * @return Collection<int, ChatThread>
     */
    public function threadsFor(Auction $auction): Collection
    {
        return $auction->chatThreads()
            ->whereNotNull('user_id')
            ->with(['user', 'latestMessage'])
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @return array{thread_id: int|null, messages: list<array<string, mixed>>}
     */
    public function roomChat(Auction $auction, ?User $user): array
    {
        $thread = $user instanceof User
            ? $this->existingThreadFor($auction, $user)
            : null;

        return [
            'thread_id' => $thread?->id,
            'messages' => $this->messagePayloads($thread),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function conversations(Auction $auction): array
    {
        return array_values(
            $this->threadsFor($auction)
                ->map(fn (ChatThread $thread) => [
                    ...$this->toThread($thread),
                    'messages' => $this->messagePayloads($thread),
                ])
                ->all(),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toMessage(ChatMessage $message, ChatThread $thread): array
    {
        return [
            'id' => $message->id,
            'thread_id' => $message->chat_thread_id,
            'auction_id' => $thread->auction_id,
            'body' => $message->body,
            'author' => $message->user->name,
            'author_id' => $message->user_id,
            'from_admin' => $message->user_id !== $thread->user_id,
            'sent_at' => $message->created_at->toIso8601String(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toThread(ChatThread $thread): array
    {
        return [
            'id' => $thread->id,
            'bidder' => $thread->user?->name,
            'bidder_id' => $thread->user_id,
            'messages_count' => $thread->messages_count ?? 0,
            'last_message' => $thread->latestMessage?->body,
            'last_message_at' => $thread->latestMessage?->created_at->toIso8601String(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function messagePayloads(?ChatThread $thread): array
    {
        if (! $thread instanceof ChatThread) {
            return [];
        }

        return array_values(
            $this->messagesFor($thread)
                ->map(fn (ChatMessage $message) => $this->toMessage($message, $thread))
                ->all(),
        );
    }
}
