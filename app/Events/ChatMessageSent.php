<?php

namespace App\Events;

use App\Models\ChatMessage;
use App\Services\ChatService;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message) {}

    /**
     * The auction-wide channel lets the admin console watch every thread
     * without one subscription per bidder.
     *
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.thread.'.$this->message->chat_thread_id),
            new PrivateChannel('chat.auction.'.$this->message->chatThread->auction_id),
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
