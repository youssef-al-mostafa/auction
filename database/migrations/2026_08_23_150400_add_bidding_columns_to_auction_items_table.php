<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->decimal('current_bid', 12, 2)->nullable()->after('starting_price');
            $table->foreignId('current_bid_id')->nullable()->after('current_bid')
                ->constrained('bids')->nullOnDelete();
            $table->foreignId('current_bidder_id')->nullable()->after('current_bid_id')
                ->constrained('users')->nullOnDelete();

            $table->timestamp('countdown_ends_at')->nullable()->after('status');
            $table->unsignedInteger('countdown_seconds')->nullable()->after('countdown_ends_at');

            $table->foreignId('winner_id')->nullable()->after('countdown_seconds')
                ->constrained('users')->nullOnDelete();
            $table->decimal('sold_price', 12, 2)->nullable()->after('winner_id');
            $table->timestamp('activated_at')->nullable()->after('sold_price');
            $table->timestamp('closed_at')->nullable()->after('activated_at');

            $table->index(['status', 'countdown_ends_at']);
        });
    }

    public function down(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->dropForeign(['current_bid_id']);
            $table->dropForeign(['current_bidder_id']);
            $table->dropForeign(['winner_id']);
            $table->dropIndex(['status', 'countdown_ends_at']);

            $table->dropColumn([
                'current_bid',
                'current_bid_id',
                'current_bidder_id',
                'countdown_ends_at',
                'countdown_seconds',
                'winner_id',
                'sold_price',
                'activated_at',
                'closed_at',
            ]);
        });
    }
};
