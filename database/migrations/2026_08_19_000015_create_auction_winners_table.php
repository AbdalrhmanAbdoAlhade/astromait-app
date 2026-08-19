<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auction_winners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auction_id')->unique()->constrained('auctions')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('final_amount', 12, 2);
            $table->foreignId('order_id')->nullable(); // FK added later in the deferred foreign-keys migration (orders table doesn't exist yet at this point)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auction_winners');
    }
};
