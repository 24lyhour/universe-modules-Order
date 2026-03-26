<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('transaction_number')->unique();

            $table->foreignId('order_id')->nullable()->constrained('order_orders')->nullOnDelete();
            $table->foreignId('refund_id')->nullable()->constrained('order_refunds')->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('type')->default('payment'); // payment, refund
            $table->string('payment_method')->nullable();

            $table->decimal('amount', 10, 2);
            $table->decimal('fee', 10, 2)->default(0);
            $table->decimal('net_amount', 10, 2);
            $table->string('currency', 3)->default('USD');

            $table->string('status')->default('pending');

            $table->string('gateway_transaction_id')->nullable();
            $table->json('gateway_response')->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('failure_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('type');
            $table->index('transaction_number');
            $table->index('gateway_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_transactions');
    }
};
