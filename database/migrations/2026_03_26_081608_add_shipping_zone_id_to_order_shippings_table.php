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
        Schema::table('order_shippings', function (Blueprint $table) {
            $table->foreignId('shipping_zone_id')
                ->nullable()
                ->after('order_id')
                ->constrained('order_shipping_zones')
                ->nullOnDelete();

            // Distance from outlet to delivery location
            $table->decimal('distance_km', 8, 2)->nullable()->after('longitude');

            $table->index('shipping_zone_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_shippings', function (Blueprint $table) {
            $table->dropForeign(['shipping_zone_id']);
            $table->dropColumn(['shipping_zone_id', 'distance_km']);
        });
    }
};
