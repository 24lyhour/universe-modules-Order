<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Product Reviews - Customers rate products/items they ordered
     * Example: "The Chicken Rice was delicious! 5 stars"
     */
    public function up(): void
    {
        Schema::create('order_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Cross-database references (customers & products live in universe DB)
            $table->unsignedBigInteger('customer_id')->index();
            $table->unsignedBigInteger('product_id')->index();
            // Same-database references
            $table->foreignId('order_id')->constrained('order_orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_order_items')->cascadeOnDelete();

            // Review content
            $table->tinyInteger('rating')->unsigned()->comment('1-5 stars');
            $table->text('comment')->nullable();
            $table->json('images')->nullable()->comment('Review images URLs');

            // Merchant reply
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(true)->comment('Verified purchase');

            // Helpful votes
            $table->unsignedInteger('helpful_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->unique(['order_item_id']); // One review per order item
            $table->index(['product_id', 'rating', 'is_active']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_product_reviews');
    }
};
