<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->timestamp('payment_deadline')->nullable()->after('closed_at');
            $table->timestamp('paid_at')->nullable()->after('payment_deadline');

            $table->index(['winner_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::table('auction_items', function (Blueprint $table) {
            $table->dropIndex(['winner_id', 'paid_at']);

            $table->dropColumn(['payment_deadline', 'paid_at']);
        });
    }
};
