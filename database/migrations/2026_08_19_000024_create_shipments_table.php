<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('carrier')->nullable(); // Aramex, DHL...
            $table->string('tracking_number')->nullable();
            $table->enum('status', ['pending', 'preparing', 'shipped', 'in_transit', 'delivered', 'failed'])->default('pending');
            $table->decimal('cost', 12, 2)->default(0);
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
