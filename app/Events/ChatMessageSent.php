<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message) {}

    /**
     * Chat is room-wide and readable without an account, so it rides the same
     * kind of public channel the bids and countdowns already use.
     *
     * @return list<Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('chat.auction.'.$this->message->chatThread->auction_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'chat.message';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => app(ChatService::class)->toMessage(
                $this->message,
                $this->message->chatThread,
            ),
            'server_time' => now()->toIso8601String(),
        ];
    }
}
