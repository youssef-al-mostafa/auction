<?php

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

/*
 * chat.auction.{id} is public for the same reason auction.{id} is: the room
 * chat is one shared conversation that anyone watching the room may read.
 * Posting still requires an account, which the route middleware enforces.
 */
