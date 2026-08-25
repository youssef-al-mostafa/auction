<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ChatMessageRequest;
use App\Models\Auction;
use App\Models\ChatThread;
use App\Services\ChatService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    public function index(Auction $auction): Response
    {
        return Inertia::render('admin/auctions/chat', [
            'auction' => [
                'id' => $auction->id,
                'slug' => $auction->slug,
                'title' => $auction->title,
                'type' => $auction->type,
                'status' => $auction->status,
            ],
            'conversations' => $this->chat->conversations($auction),
        ]);
    }

    public function store(ChatMessageRequest $request, ChatThread $thread): RedirectResponse
    {
        $this->chat->post($thread, $request->user(), (string) $request->validated('body'));

        return back();
    }
}
