<?php

namespace App\Services;

use App\Enums\PermissionsEnum;
use App\Events\ChatMessageSent;
use App\Models\Auction;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    /**
     * Spatie resolves a permission check through these relations, so messages
     * load them up front rather than firing two queries per author.
     *
     * @var list<string>
     */
    private const AUTHOR_RELATIONS = ['user.roles.permissions', 'user.permissions'];

    /**
     * The room-wide thread every participant in an auction shares.
     *
     * A null user_id marks it. Threads carrying a user_id are the per-bidder
     * conversations that predate the group chat; nothing reads them any more.
     */
    public function threadFor(Auction $auction): ChatThread
    {
        return ChatThread::firstOrCreate([
            'auction_id' => $auction->id,
            'user_id' => null,
        ]);
    }

    public function existingThreadFor(Auction $auction): ?ChatThread
    {
        return ChatThread::query()
            ->where('auction_id', $auction->id)
            ->whereNull('user_id')
            ->first();
    }

    public function post(ChatThread $thread, User $user, string $body): ChatMessage
    {
        $message = $thread->messages()->create([
            'user_id' => $user->id,
            'body' => $body,
        ]);

        $thread->touch();

        ChatMessageSent::dispatch($message->load(self::AUTHOR_RELATIONS));

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
            ->with(self::AUTHOR_RELATIONS)
            ->inSendOrder()
            ->take($limit)
            ->get();
    }

    /**
     * The room chat is the same for everyone, so it takes no viewer: logged-out
     * visitors read exactly what bidders and the admin read.
     *
     * @return array{thread_id: int|null, messages: list<array<string, mixed>>}
     */
    public function roomChat(Auction $auction): array
    {
        $thread = $this->existingThreadFor($auction);

        return [
            'thread_id' => $thread?->id,
            'messages' => $this->messagePayloads($thread),
        ];
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
            'from_admin' => $message->user->can(PermissionsEnum::MANAGE_AUCTIONS->value),
            'sent_at' => $message->created_at->toIso8601String(),
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
