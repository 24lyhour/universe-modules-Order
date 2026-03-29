<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Updates order status enum to match Cambodia e-commerce workflow:
     * pending → confirmed → preparing → ready → delivering → delivered → completed
     */
    public function up(): void
    {
        // First, update existing data to map old values to new values
        DB::table('order_orders')
            ->where('status', 'processing')
            ->update(['status' => 'preparing']);

        DB::table('order_orders')
            ->where('status', 'shipped')
            ->update(['status' => 'delivering']);

        // Alter the enum column to include new values
        DB::statement("ALTER TABLE order_orders MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'preparing',
            'ready',
            'delivering',
            'delivered',
            'completed',
            'cancelled',
            'refunded'
        ) DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Map new values back to old values
        DB::table('order_orders')
            ->where('status', 'preparing')
            ->update(['status' => 'processing']);

        DB::table('order_orders')
            ->where('status', 'delivering')
            ->update(['status' => 'shipped']);

        DB::table('order_orders')
            ->where('status', 'ready')
            ->update(['status' => 'confirmed']);

        DB::table('order_orders')
            ->where('status', 'completed')
            ->update(['status' => 'delivered']);

        // Revert enum column
        DB::statement("ALTER TABLE order_orders MODIFY COLUMN status ENUM(
            'pending',
            'confirmed',
            'processing',
            'shipped',
            'delivered',
            'cancelled',
            'refunded'
        ) DEFAULT 'pending'");
    }
};
