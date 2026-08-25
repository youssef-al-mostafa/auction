<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('idempotency_key', 64);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auction_item_id', 'amount', 'created_at']);
            $table->unique(['auction_item_id', 'idempotency_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
