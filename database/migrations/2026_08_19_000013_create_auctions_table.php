<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('vendor_profiles')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('start_price', 12, 2);
            $table->decimal('current_price', 12, 2);
            $table->decimal('min_bid_increment', 12, 2)->default(10);
            $table->timestamp('start_at');
            $table->timestamp('end_at');
            $table->enum('status', ['scheduled', 'live', 'closed', 'cancelled'])->default('scheduled');
            $table->timestamps();

            $table->index(['status', 'start_at', 'end_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
