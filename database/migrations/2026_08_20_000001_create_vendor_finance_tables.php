<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_profile_id')->unique()->constrained('vendor_profiles')->cascadeOnDelete();
            $table->string('currency', 3)->default('SAR');
            $table->decimal('pending_balance', 14, 2)->default(0);
            $table->decimal('available_balance', 14, 2)->default(0);
            $table->decimal('held_balance', 14, 2)->default(0);
            $table->decimal('paid_balance', 14, 2)->default(0);
            $table->decimal('gross_sales', 14, 2)->default(0);
            $table->decimal('platform_commission', 14, 2)->default(0);
            $table->decimal('refunded_amount', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('vendor_payout_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_profile_id')->constrained('vendor_profiles')->cascadeOnDelete();
            $table->string('type')->default('bank');
            $table->string('holder_name');
            $table->string('bank_name')->nullable();
            $table->text('iban')->nullable();
            $table->text('account_number')->nullable();
            $table->string('currency', 3)->default('SAR');
            $table->boolean('is_default')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['vendor_profile_id', 'is_default']);
        });

        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('vendor_wallets')->cascadeOnDelete();
            $table->string('type');
            $table->decimal('amount', 14, 2);
            $table->decimal('pending_delta', 14, 2)->default(0);
            $table->decimal('available_delta', 14, 2)->default(0);
            $table->decimal('held_delta', 14, 2)->default(0);
            $table->decimal('paid_delta', 14, 2)->default(0);
            $table->decimal('pending_balance', 14, 2);
            $table->decimal('available_balance', 14, 2);
            $table->decimal('held_balance', 14, 2);
            $table->decimal('paid_balance', 14, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('idempotency_key')->unique();
            $table->nullableMorphs('reference');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
            $table->unsignedBigInteger('payout_request_id')->nullable()->index();
            $table->json('metadata')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['wallet_id', 'type']);
            $table->index(['order_id', 'order_item_id']);
        });

        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_profile_id')->constrained('vendor_profiles')->cascadeOnDelete();
            $table->string('settlement_number')->unique();
            $table->date('period_start');
            $table->date('period_end');
            $table->string('status')->default('draft');
            $table->decimal('gross_sales', 14, 2)->default(0);
            $table->decimal('platform_commission', 14, 2)->default(0);
            $table->decimal('refunds', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('SAR');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['vendor_profile_id', 'status']);
        });

        Schema::create('settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->cascadeOnDelete();
            $table->decimal('gross_amount', 14, 2);
            $table->decimal('commission_amount', 14, 2);
            $table->decimal('refund_amount', 14, 2)->default(0);
            $table->decimal('net_amount', 14, 2);
            $table->timestamps();
        });

        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_profile_id')->constrained('vendor_profiles')->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained('vendor_wallets')->cascadeOnDelete();
            $table->foreignId('settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
            $table->foreignId('payout_account_id')->nullable()->constrained('vendor_payout_accounts')->nullOnDelete();
            $table->string('payout_number')->unique();
            $table->decimal('amount', 14, 2);
            $table->string('currency', 3)->default('SAR');
            $table->string('status')->default('pending');
            $table->string('method')->default('bank_transfer');
            $table->json('account_snapshot')->nullable();
            $table->string('provider_reference')->nullable()->unique();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['vendor_profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('payout_requests');
        Schema::dropIfExists('settlement_items');
        Schema::dropIfExists('settlements');
        Schema::dropIfExists('vendor_payout_accounts');
        Schema::dropIfExists('vendor_wallets');
    }
};
