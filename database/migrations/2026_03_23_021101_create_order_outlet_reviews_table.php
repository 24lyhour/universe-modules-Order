<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Outlet Reviews - Customers rate the outlet/store service
     * Example: "Fast delivery, friendly staff! 5 stars"
     */
    public function up(): void
    {
        Schema::create('order_outlet_reviews', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relationships
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('order_orders')->cascadeOnDelete();
            $table->foreignId('outlet_id')->constrained()->cascadeOnDelete();

            // Rating categories (like Grab/Foodpanda)
            $table->tinyInteger('overall_rating')->unsigned()->comment('1-5 stars overall');
            $table->tinyInteger('food_rating')->unsigned()->nullable()->comment('1-5 food quality');
            $table->tinyInteger('service_rating')->unsigned()->nullable()->comment('1-5 service quality');
            $table->tinyInteger('delivery_rating')->unsigned()->nullable()->comment('1-5 delivery speed');
            $table->tinyInteger('packaging_rating')->unsigned()->nullable()->comment('1-5 packaging quality');

            // Review content
            $table->text('comment')->nullable();
            $table->json('images')->nullable()->comment('Review images URLs');
            $table->json('tags')->nullable()->comment('Quick tags: fast, friendly, clean, etc.');

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
            $table->unique(['order_id']); // One review per order
            $table->index(['outlet_id', 'overall_rating', 'is_active']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_outlet_reviews');
    }
};
