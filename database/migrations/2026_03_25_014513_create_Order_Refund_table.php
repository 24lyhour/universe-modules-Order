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
        Schema::create('order_refunds', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('refund_number')->unique();

            // Same-database reference
            $table->foreignId('order_id')->constrained('order_orders')->cascadeOnDelete();
            // Cross-database references (customers, outlets, users live in universe DB)
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('outlet_id')->nullable()->index();

            $table->decimal('amount', 10, 2);
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->string('status')->default('pending');

            $table->unsignedBigInteger('approved_by')->nullable()->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('refund_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_refunds');
    }
};
