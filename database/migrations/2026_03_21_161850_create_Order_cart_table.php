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
        // Create order_carts table
        Schema::create('order_carts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Cross-database references (customers & outlets live in universe DB)
            $table->unsignedBigInteger('customer_id')->nullable()->index();
            $table->unsignedBigInteger('outlet_id')->nullable()->index();

            // Data
            $table->enum('status', ['active', 'abandoned', 'converted', 'expired'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('expires_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes
            $table->index(['customer_id', 'status']);
            $table->index('expires_at');
        });

        // Create order_cart_items table
        Schema::create('order_cart_items', function (Blueprint $table) {
            $table->id();

            // Relationships
            $table->foreignId('cart_id')->constrained('order_carts')->cascadeOnDelete();
            // Cross-database reference (products live in universe DB)
            $table->unsignedBigInteger('product_id')->index();

            // Data
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('total_amount', 10, 2);
            $table->text('notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['cart_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_cart_items');
        Schema::dropIfExists('order_carts');
    }
};
