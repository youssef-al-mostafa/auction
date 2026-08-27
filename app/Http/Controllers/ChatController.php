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
        $this->chat->post(
            $this->chat->threadFor($auction),
            $request->user(),
            (string) $request->validated('body'),
        );

        return back();
    }
}
