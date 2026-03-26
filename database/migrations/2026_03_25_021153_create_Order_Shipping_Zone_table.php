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
        Schema::create('order_shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();

            // Relation to outlet (each outlet can have multiple delivery zones)
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();

            // Zone info
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6'); // Hex color for map display

            // Geofence type: 'circle' or 'polygon'
            $table->enum('zone_type', ['circle', 'polygon'])->default('circle');

            // Center point (for circle) or reference point (for polygon)
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);

            // For circle type - radius in meters
            $table->unsignedInteger('radius')->nullable();

            // For polygon type - array of coordinates [[lat, lng], [lat, lng], ...]
            $table->json('polygon_coordinates')->nullable();

            // ========== DELIVERY PRICING ==========
            $table->decimal('delivery_fee', 10, 2)->default(0);
            $table->decimal('min_order_amount', 10, 2)->default(0);
            $table->decimal('free_delivery_threshold', 10, 2)->nullable(); // Free delivery above this amount
            $table->decimal('peak_hour_surcharge', 10, 2)->default(0); // Extra fee during peak hours
            $table->decimal('price_per_km', 10, 2)->nullable(); // Dynamic pricing per kilometer

            // ========== CAPACITY LIMITS ==========
            $table->unsignedInteger('max_orders_per_hour')->nullable(); // Capacity limit
            $table->decimal('max_weight_kg', 8, 2)->nullable(); // Maximum order weight
            $table->unsignedInteger('max_items')->nullable(); // Maximum items per order

            // ========== VEHICLE / DRIVER ==========
            $table->enum('vehicle_type', ['bike', 'motorcycle', 'car', 'van', 'truck'])->default('motorcycle');
            $table->text('driver_requirements')->nullable(); // Special requirements for drivers
            $table->boolean('requires_special_handling')->default(false);

            // ========== TIME SETTINGS ==========
            $table->unsignedInteger('estimated_delivery_minutes')->nullable();
            $table->json('operating_hours')->nullable(); // {"monday": {"open": "08:00", "close": "22:00"}, ...}
            $table->json('peak_hours')->nullable(); // {"start": "11:00", "end": "14:00"} for surcharge
            $table->json('blocked_dates')->nullable(); // ["2026-01-01", "2026-12-25"] holidays

            // ========== STATUS & PRIORITY ==========
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(0); // Lower = higher priority

            // ========== AUDIT ==========
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['outlet_id', 'is_active']);
            $table->index(['latitude', 'longitude']);
            $table->index('zone_type');
            $table->index('vehicle_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_shipping_zones');
    }
};
