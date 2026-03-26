<?php

namespace Modules\Order\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderShipping;

class OrderUpdateShippingCoordsCommand extends Command
{
    protected $signature = 'order:update-shipping-coords
                            {--all : Update all shipping records}
                            {--create-missing : Create shipping for orders without it}';

    protected $description = 'Add/update coordinates for order shipping records';

    // Sample Cambodia locations
    private array $locations = [
        ['city' => 'Phnom Penh', 'state' => 'Phnom Penh', 'lat' => 11.5564, 'lng' => 104.9282],
        ['city' => 'Sen Sok', 'state' => 'Phnom Penh', 'lat' => 11.5869, 'lng' => 104.8636],
        ['city' => 'Chamkarmon', 'state' => 'Phnom Penh', 'lat' => 11.5449, 'lng' => 104.9282],
        ['city' => 'Toul Kork', 'state' => 'Phnom Penh', 'lat' => 11.5780, 'lng' => 104.8999],
        ['city' => 'Meanchey', 'state' => 'Phnom Penh', 'lat' => 11.5200, 'lng' => 104.8900],
        ['city' => 'BKK1', 'state' => 'Phnom Penh', 'lat' => 11.5498, 'lng' => 104.9205],
    ];

    private array $streets = [
        'Street 271', 'Street 63', 'Monivong Boulevard', 'Norodom Boulevard',
        'Street 310', 'Street 178', 'Russian Boulevard', 'Street 2004',
    ];

    public function handle(): int
    {
        $createMissing = $this->option('create-missing');
        $updateAll = $this->option('all');

        // First, create missing shipping records
        if ($createMissing) {
            $this->createMissingShipping();
        }

        // Then update coordinates
        $query = OrderShipping::query();
        if (!$updateAll && !$createMissing) {
            $query->where(function ($q) {
                $q->whereNull('latitude')->orWhereNull('longitude');
            });
        }

        $shippings = $query->with('order')->get();

        if ($shippings->isEmpty()) {
            $this->info('✓ All shipping records have coordinates!');
            return Command::SUCCESS;
        }

        $this->info("Updating {$shippings->count()} shipping record(s)...");
        $this->newLine();

        foreach ($shippings as $shipping) {
            $location = $this->locations[array_rand($this->locations)];
            $latOffset = (rand(-200, 200) / 10000);
            $lngOffset = (rand(-200, 200) / 10000);

            $shipping->update([
                'latitude' => $location['lat'] + $latOffset,
                'longitude' => $location['lng'] + $lngOffset,
                'city' => $shipping->city ?: $location['city'],
                'state' => $shipping->state ?: $location['state'],
                'country' => $shipping->country ?: 'Cambodia',
            ]);

            $orderNum = $shipping->order?->order_number ?? "Order #{$shipping->order_id}";
            $this->line("✓ <fg=yellow>{$orderNum}</> → {$location['city']} ({$shipping->latitude}, {$shipping->longitude})");
        }

        $this->newLine();
        $this->info("✅ Updated {$shippings->count()} shipping record(s)!");

        return Command::SUCCESS;
    }

    /**
     * Create shipping records for orders that don't have one.
     */
    private function createMissingShipping(): void
    {
        $ordersWithoutShipping = Order::whereDoesntHave('shipping')
            ->with('customer')
            ->get();

        if ($ordersWithoutShipping->isEmpty()) {
            $this->info('✓ All orders have shipping records.');
            return;
        }

        $this->info("Creating shipping for {$ordersWithoutShipping->count()} order(s)...");

        foreach ($ordersWithoutShipping as $order) {
            $location = $this->locations[array_rand($this->locations)];
            $latOffset = (rand(-200, 200) / 10000);
            $lngOffset = (rand(-200, 200) / 10000);

            OrderShipping::create([
                'order_id' => $order->id,
                'carrier' => 'Local Delivery',
                'method' => collect(['motorcycle', 'car', 'express'])->random(),
                'shipping_cost' => rand(2, 10) * 0.5,
                'tracking_number' => 'LD' . strtoupper(Str::random(4)) . rand(10000000, 99999999),
                'recipient_name' => $order->customer?->name ?? 'Customer',
                'phone' => $order->customer?->phone ?? '0' . rand(10, 99) . '-' . rand(100, 999) . '-' . rand(100, 999),
                'street_1' => $this->streets[array_rand($this->streets)] . ', #' . rand(1, 999),
                'city' => $location['city'],
                'state' => $location['state'],
                'postal_code' => rand(10000, 99999),
                'country' => 'Cambodia',
                'latitude' => $location['lat'] + $latOffset,
                'longitude' => $location['lng'] + $lngOffset,
                'estimated_delivery_at' => now()->addDays(rand(1, 3)),
            ]);

            $this->line("  + Created shipping for <fg=green>{$order->order_number}</>");
        }

        $this->newLine();
    }
}
