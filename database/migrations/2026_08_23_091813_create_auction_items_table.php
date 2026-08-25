<?php

use App\Enums\AuctionItemStatusEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->decimal('starting_price', 12, 2);
            $table->string('status')->default(AuctionItemStatusEnum::PENDING->value);
            $table->timestamps();

            $table->unique(['auction_id', 'product_id']);
            $table->index(['auction_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_items');
    }
};
