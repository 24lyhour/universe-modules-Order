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
        // Create order_orders table
        Schema::create('order_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('order_number')->unique();

            // Relationships
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('outlet_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained('order_carts')->nullOnDelete();

            // Pricing
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);

            // Status
            $table->enum('status', [
                'pending',
                'confirmed',
                'processing',
                'shipped',
                'delivered',
                'cancelled',
                'refunded',
            ])->default('pending');

            // Payment
            $table->enum('payment_status', [
                'pending',
                'paid',
                'failed',
                'refunded',
            ])->default('pending');
            $table->string('payment_method')->nullable();

            // Additional info
            $table->text('notes')->nullable();

            // Timestamps
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('order_number');
            $table->index(['customer_id', 'status']);
            $table->index('status');
            $table->index('payment_status');
        });

        // Create order_order_items table
        Schema::create('order_order_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('order_id')->constrained('order_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            // Product snapshot (in case product is deleted)
            $table->string('product_name');
            $table->string('product_sku')->nullable();

            // Pricing
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2);

            // Additional info
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['order_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_order_items');
        Schema::dropIfExists('order_orders');
    }
};
