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
        Schema::create('order_shippings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('order_orders')->cascadeOnDelete();

            // Shipping Method
            $table->string('carrier')->nullable(); // e.g., "DHL", "FedEx", "Local Delivery"
            $table->string('method')->nullable(); // e.g., "Standard", "Express", "Same Day"
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->string('tracking_number')->nullable();

            // Shipping Address
            $table->string('recipient_name');
            $table->string('phone')->nullable();
            $table->string('street_1');
            $table->string('street_2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Cambodia');

            // GPS Coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();

            // Additional
            $table->decimal('weight', 8, 2)->nullable(); // in kg
            $table->text('notes')->nullable();
            $table->timestamp('estimated_delivery_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('tracking_number');
            $table->index('carrier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shippings');
    }
};
