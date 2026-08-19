<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// This migration adds foreign keys that reference the "orders" table,
// which did not exist yet when auction_winners / coupon_usages were created.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auction_winners', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->nullOnDelete();
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->foreign('order_id')->references('id')->on('orders')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('auction_winners', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropForeign(['order_id']);
        });
    }
};
