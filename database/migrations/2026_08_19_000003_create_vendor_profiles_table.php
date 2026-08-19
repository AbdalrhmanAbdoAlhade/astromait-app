<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('store_name');
            $table->string('store_slug')->unique();
            $table->string('store_logo')->nullable();
            $table->string('store_cover')->nullable();
            $table->text('bio')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->decimal('commission_rate', 5, 2)->default(10.00);
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_profiles');
    }
};
