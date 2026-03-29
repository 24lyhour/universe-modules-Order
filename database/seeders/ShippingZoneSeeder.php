<?php

namespace Modules\Order\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Order\Enums\VehicleTypeEnum;
use Modules\Order\Enums\ZoneTypeEnum;
use Modules\Order\Models\ShippingZone;
use Modules\Outlet\Models\Outlet;

class ShippingZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $outlets = Outlet::all();

        if ($outlets->isEmpty()) {
            $this->command->warn('No outlets found. Please run OutletSeeder first.');
            return;
        }

        foreach ($outlets as $outlet) {
            $this->createZonesForOutlet($outlet);
        }

        $this->command->info('Shipping zones seeded successfully. Total: ' . ShippingZone::count());
    }

    /**
     * Create shipping zones for an outlet.
     */
    protected function createZonesForOutlet(Outlet $outlet): void
    {
        // Skip if outlet has no coordinates
        if (!$outlet->latitude || !$outlet->longitude) {
            return;
        }

        $zones = [
            // Zone 1: Inner circle (walking/bike distance)
            [
                'name' => $outlet->name . ' - Inner Zone',
                'description' => '<p>Closest delivery zone with fastest delivery times.</p>',
                'color' => '#22c55e', // Green
                'zone_type' => ZoneTypeEnum::Circle,
                'latitude' => $outlet->latitude,
                'longitude' => $outlet->longitude,
                'radius' => 1500, // 1.5km
                'delivery_fee' => 1.00,
                'min_order_amount' => 5.00,
                'free_delivery_threshold' => 25.00,
                'peak_hour_surcharge' => 0.50,
                'vehicle_type' => VehicleTypeEnum::Bike,
                'estimated_delivery_minutes' => 15,
                'priority' => 1,
                'is_active' => true,
            ],
            // Zone 2: Standard delivery zone
            [
                'name' => $outlet->name . ' - Standard Zone',
                'description' => '<p>Standard delivery zone for most customers.</p>',
                'color' => '#3b82f6', // Blue
                'zone_type' => ZoneTypeEnum::Circle,
                'latitude' => $outlet->latitude,
                'longitude' => $outlet->longitude,
                'radius' => 3000, // 3km
                'delivery_fee' => 2.00,
                'min_order_amount' => 10.00,
                'free_delivery_threshold' => 40.00,
                'peak_hour_surcharge' => 1.00,
                'vehicle_type' => VehicleTypeEnum::Motorcycle,
                'estimated_delivery_minutes' => 25,
                'priority' => 2,
                'is_active' => true,
            ],
            // Zone 3: Extended delivery zone
            [
                'name' => $outlet->name . ' - Extended Zone',
                'description' => '<p>Extended delivery area with longer delivery times.</p>',
                'color' => '#f59e0b', // Yellow/Orange
                'zone_type' => ZoneTypeEnum::Circle,
                'latitude' => $outlet->latitude,
                'longitude' => $outlet->longitude,
                'radius' => 5000, // 5km
                'delivery_fee' => 3.50,
                'min_order_amount' => 15.00,
                'free_delivery_threshold' => 60.00,
                'peak_hour_surcharge' => 1.50,
                'price_per_km' => 0.50,
                'vehicle_type' => VehicleTypeEnum::Motorcycle,
                'estimated_delivery_minutes' => 40,
                'priority' => 3,
                'is_active' => true,
            ],
            // Zone 4: Far delivery zone (car only)
            [
                'name' => $outlet->name . ' - Far Zone',
                'description' => '<p>Distant delivery zone requiring vehicle delivery.</p>',
                'color' => '#ef4444', // Red
                'zone_type' => ZoneTypeEnum::Circle,
                'latitude' => $outlet->latitude,
                'longitude' => $outlet->longitude,
                'radius' => 10000, // 10km
                'delivery_fee' => 5.00,
                'min_order_amount' => 25.00,
                'free_delivery_threshold' => 100.00,
                'peak_hour_surcharge' => 2.00,
                'price_per_km' => 0.75,
                'vehicle_type' => VehicleTypeEnum::Car,
                'estimated_delivery_minutes' => 60,
                'max_weight_kg' => 50,
                'max_items' => 20,
                'priority' => 4,
                'is_active' => true,
            ],
        ];

        foreach ($zones as $zone) {
            ShippingZone::firstOrCreate(
                [
                    'outlet_id' => $outlet->id,
                    'name' => $zone['name'],
                ],
                array_merge($zone, [
                    'uuid' => Str::uuid(),
                    'outlet_id' => $outlet->id,
                ])
            );
        }
    }
}
