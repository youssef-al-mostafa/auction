<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChatMessageRequest;
use App\Models\Auction;
use App\Services\ChatService;
use Illuminate\Http\RedirectResponse;

class ChatController extends Controller
{
    public function __construct(private readonly ChatService $chat) {}

    public function store(ChatMessageRequest $request, Auction $auction): RedirectResponse
    {
        $user = $request->user();
        $thread = $this->chat->threadFor($auction, $user);

        $this->chat->post($thread, $user, (string) $request->validated('body'));

        return back();
    }
}
