<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Each auction has one room-wide chat thread, identified by a null user_id.
     *
     * The existing unique on (auction_id, user_id) cannot enforce that: Postgres
     * treats nulls as distinct, so nothing stops two group threads racing into
     * existence for the same auction. A partial unique index closes that gap
     * while leaving the per-bidder threads that predate the group chat alone.
     */
    public function up(): void
    {
        DB::statement(
            'CREATE UNIQUE INDEX chat_threads_group_unique ON chat_threads (auction_id) WHERE user_id IS NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS chat_threads_group_unique');
    }
};
