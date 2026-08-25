<?php

use App\Enums\PermissionsEnum;
use App\Models\ChatThread;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

/*
 * auction.{id} is public on purpose: bids, countdowns and results are the shared
 * state of a room everyone is watching. Nothing user-specific travels on it.
 */
Broadcast::channel('user.{id}', function (User $user, int $id) {
    return $user->id === $id;
});

Broadcast::channel('chat.thread.{threadId}', function (User $user, int $threadId) {
    $thread = ChatThread::find($threadId);

    if (! $thread instanceof ChatThread) {
        return false;
    }

    return $thread->isOwnedBy($user)
        || $user->can(PermissionsEnum::MANAGE_AUCTIONS->value);
});

Broadcast::channel('chat.auction.{auctionId}', function (User $user, int $auctionId) {
    return $user->can(PermissionsEnum::MANAGE_AUCTIONS->value);
});
