<?php

namespace Modules\Order\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\CartStatusEnum;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Enums\PaymentMethodEnum;
use Modules\Order\Enums\PaymentStatusEnum;
use Modules\Order\Models\Cart;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderShipping;
use Modules\Order\Models\ShippingZone;
use Modules\Outlet\Models\Outlet;
use Modules\Product\Models\Product;

class OrderPushCommand extends Command
{
    protected $signature = 'order:push {--count=1 : Number of orders}';

    protected $description = 'Push/create orders (auto-creates products if needed)';

    public function handle(): int
    {
        $count = (int) $this->option('count');

        // Auto-create products if none exist
        $products = Product::where('status', 'active')->where('stock', '>', 0)->get();
        if ($products->isEmpty()) {
            $this->warn('No products found. Creating sample products...');
            $products = $this->createSampleProducts();
            $this->info("✓ Created {$products->count()} sample products");
            $this->newLine();
        }

        // Auto-create outlet if none exist
        $outlets = Outlet::all();
        if ($outlets->isEmpty()) {
            $this->warn('No outlets found. Creating sample outlet...');
            $outlet = Outlet::create([
                'uuid' => Str::uuid(),
                'name' => 'Main Store',
                'address' => '123 Main Street',
                'phone' => '012-345-678',
                'is_active' => true,
            ]);
            $outlets = collect([$outlet]);
            $this->info("✓ Created outlet: {$outlet->name}");
            $this->newLine();
        }

        $customers = Customer::all();

        $this->info("🛒 Creating {$count} Order(s)...");
        $this->line('================================');
        $this->newLine();

        for ($i = 0; $i < $count; $i++) {
            $order = $this->createOrder($customers, $outlets, $products);
            $this->displayOrder($order, $i + 1);
        }

        $this->info('✅ Done! View all orders: php artisan order:list');
        $this->newLine();

        return Command::SUCCESS;
    }

    protected function createSampleProducts()
    {
        $outlet = Outlet::first();

        $items = [
            ['name' => 'Burger', 'price' => 8.99, 'sku' => 'FOOD-001'],
            ['name' => 'Pizza', 'price' => 12.99, 'sku' => 'FOOD-002'],
            ['name' => 'Pasta', 'price' => 10.99, 'sku' => 'FOOD-003'],
            ['name' => 'Coffee', 'price' => 3.99, 'sku' => 'BEV-001'],
            ['name' => 'Juice', 'price' => 4.99, 'sku' => 'BEV-002'],
            ['name' => 'Cake', 'price' => 6.99, 'sku' => 'DSRT-001'],
            ['name' => 'Ice Cream', 'price' => 5.99, 'sku' => 'DSRT-002'],
            ['name' => 'Salad', 'price' => 7.99, 'sku' => 'FOOD-004'],
        ];

        $products = collect();
        foreach ($items as $item) {
            $products->push(Product::create([
                'uuid' => Str::uuid(),
                'name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'sku' => $item['sku'],
                'price' => $item['price'],
                'stock' => 100,
                'status' => 'active',
                'outlet_id' => $outlet?->id,
            ]));
        }

        return $products;
    }

    protected function createOrder($customers, $outlets, $products): Order
    {
        $customer = $customers->isNotEmpty() && rand(1, 100) <= 70
            ? $customers->random()
            : null;

        $outlet = $outlets->random();

        // Create cart
        $cart = Cart::create([
            'uuid' => Str::uuid(),
            'customer_id' => $customer?->id,
            'outlet_id' => $outlet->id,
            'status' => CartStatusEnum::Active->value,
            'is_active' => true,
        ]);

        // Add 1-4 products
        $itemCount = rand(1, min(4, $products->count()));
        $selectedProducts = $products->random($itemCount);
        $subtotal = 0;

        foreach ($selectedProducts as $product) {
            $qty = rand(1, 3);
            $price = $product->sale_price ?? $product->price;
            $total = $price * $qty;
            $subtotal += $total;

            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $qty,
                'unit_price' => $price,
                'total_amount' => $total,
            ]);
        }

        // Create order
        $tax = round($subtotal * 0.10, 2);
        $paymentMethod = collect(PaymentMethodEnum::values())->random();

        $order = Order::create([
            'uuid' => Str::uuid(),
            'order_number' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5)),
            'customer_id' => $customer?->id,
            'outlet_id' => $outlet->id,
            'subtotal' => $subtotal,
            'discount_amount' => 0,
            'tax_amount' => $tax,
            'total_amount' => $subtotal + $tax,
            'status' => OrderStatusEnum::Pending->value,
            'payment_status' => PaymentStatusEnum::Pending->value,
            'payment_method' => $paymentMethod,
        ]);

        foreach ($cart->items as $item) {
            $order->items()->create([
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Product',
                'product_sku' => $item->product?->sku,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'discount_amount' => 0,
                'total_amount' => $item->total_amount,
            ]);
        }

        // Create shipping info with customer address and zone detection
        $shippingData = $this->generateShippingData($customer, $outlet);

        // Find shipping zone based on coordinates
        $zone = null;
        $distanceKm = null;
        if (isset($shippingData['latitude']) && isset($shippingData['longitude'])) {
            $zone = ShippingZone::getBestZoneForPoint(
                (float) $shippingData['latitude'],
                (float) $shippingData['longitude'],
                $outlet->id
            );

            if ($zone) {
                $distanceKm = $zone->getDistanceToPoint(
                    (float) $shippingData['latitude'],
                    (float) $shippingData['longitude']
                );
                // Use zone's calculated delivery fee
                $shippingData['shipping_cost'] = $zone->calculateDeliveryFee(
                    (float) $shippingData['latitude'],
                    (float) $shippingData['longitude'],
                    $order->total_amount
                );
            }
        }

        OrderShipping::create([
            'order_id' => $order->id,
            'shipping_zone_id' => $zone?->id,
            'distance_km' => $distanceKm ? round($distanceKm, 2) : null,
            ...$shippingData,
        ]);

        $cart->update(['status' => CartStatusEnum::Converted->value, 'is_active' => false]);

        return $order->fresh(['customer', 'outlet', 'items', 'shipping.shippingZone']);
    }

    protected function generateShippingData(?Customer $customer, Outlet $outlet): array
    {
        // Sample street names for random address generation
        $streets = [
            'Monivong Blvd', 'Norodom Blvd', 'Sihanouk Blvd', 'Mao Tse Tung Blvd',
            'Street 51', 'Street 63', 'Street 178', 'Street 240', 'Street 310',
            'Kampuchea Krom Blvd', 'Russian Blvd', 'Confederation de la Russie',
        ];

        $cities = ['Phnom Penh', 'Siem Reap', 'Battambang', 'Sihanoukville', 'Kampot'];

        // Use customer data if available, otherwise generate random
        if ($customer) {
            $recipientName = $customer->name;
            $phone = $customer->phone ?? '0' . rand(10, 99) . '-' . rand(100, 999) . '-' . rand(100, 999);
            $address = $customer->address;

            // Parse customer address or use default
            if ($address) {
                return [
                    'recipient_name' => $recipientName,
                    'phone' => $phone,
                    'street_1' => $address,
                    'city' => 'Phnom Penh',
                    'state' => 'Phnom Penh',
                    'country' => 'Cambodia',
                    'latitude' => 11.55 + (rand(-100, 100) / 1000),
                    'longitude' => 104.92 + (rand(-100, 100) / 1000),
                    'shipping_cost' => rand(1, 5) * 0.5,
                ];
            }
        }

        // Generate random address for guest or customer without address
        $streetNum = rand(1, 500);
        $street = $streets[array_rand($streets)];
        $city = $cities[array_rand($cities)];

        return [
            'recipient_name' => $customer?->name ?? 'Guest Customer',
            'phone' => $customer?->phone ?? '0' . rand(10, 99) . '-' . rand(100, 999) . '-' . rand(100, 999),
            'street_1' => "#{$streetNum}, {$street}",
            'street_2' => 'Sangkat ' . ['Tonle Bassac', 'Boeung Keng Kang', 'Chamkar Mon', 'Daun Penh'][rand(0, 3)],
            'city' => $city,
            'state' => $city === 'Phnom Penh' ? 'Phnom Penh' : $city . ' Province',
            'postal_code' => '12' . rand(100, 999),
            'country' => 'Cambodia',
            'latitude' => 11.55 + (rand(-100, 100) / 1000),
            'longitude' => 104.92 + (rand(-100, 100) / 1000),
            'shipping_cost' => rand(1, 5) * 0.5,
        ];
    }

    protected function displayOrder(Order $order, int $num): void
    {
        $this->line("┌─────────────────────────────────────────────────────┐");
        $this->line("│  📦 ORDER #{$num}                                        │");
        $this->line("├─────────────────────────────────────────────────────┤");
        $this->line("│  Order #: <fg=yellow>{$order->order_number}</>              │");
        $this->line("│  Date: {$order->created_at->format('Y-m-d H:i')}                        │");
        $this->line("├─────────────────────────────────────────────────────┤");

        // Customer
        $custName = $order->customer?->name ?? 'Guest';
        $custId = $order->customer_id ?? '-';
        $this->line("│  👤 Customer: {$custName} (ID: {$custId})");

        // Shipping Address
        if ($order->shipping) {
            $this->line("│  📍 Address: {$order->shipping->recipient_name}");
            $this->line("│     {$order->shipping->street_1}");
            if ($order->shipping->street_2) {
                $this->line("│     {$order->shipping->street_2}");
            }
            $this->line("│     {$order->shipping->city}, {$order->shipping->country}");
            $this->line("│     📞 {$order->shipping->phone}");
            if ($order->shipping->latitude && $order->shipping->longitude) {
                $this->line("│     🗺️  GPS: ({$order->shipping->latitude}, {$order->shipping->longitude})");
            }
            if ($order->shipping->shippingZone) {
                $zone = $order->shipping->shippingZone;
                $this->line("│     📦 Zone: <fg=cyan>{$zone->name}</> (Fee: \${$order->shipping->shipping_cost})");
                if ($order->shipping->distance_km) {
                    $this->line("│     📏 Distance: {$order->shipping->distance_km} km");
                }
            }
        }

        // Outlet
        $this->line("│  🏪 Outlet: {$order->outlet?->name} (ID: {$order->outlet_id})");
        if ($order->outlet?->latitude && $order->outlet?->longitude) {
            $this->line("│     🗺️  GPS: ({$order->outlet->latitude}, {$order->outlet->longitude})");
        }
        $this->line("├─────────────────────────────────────────────────────┤");

        // Products
        $this->line("│  📋 PRODUCTS:");
        foreach ($order->items as $i => $item) {
            $n = $i + 1;
            $name = Str::limit($item->product_name, 15);
            $this->line("│     {$n}. {$name} x{$item->quantity} = \${$item->total_amount}");
        }

        $this->line("├─────────────────────────────────────────────────────┤");
        $this->line("│  💰 Subtotal: \$" . number_format($order->subtotal, 2));
        if ($order->shipping?->shipping_cost) {
            $this->line("│     Shipping: \$" . number_format($order->shipping->shipping_cost, 2));
        }
        $this->line("│     Tax 10%: \$" . number_format($order->tax_amount, 2));
        $this->line("│     <fg=green>TOTAL: \$" . number_format($order->total_amount, 2) . "</>");
        $this->line("├─────────────────────────────────────────────────────┤");
        $this->line("│  Payment: {$order->payment_method} ({$order->payment_status->value})");
        $this->line("│  Status: {$order->status->value}");
        $this->line("└─────────────────────────────────────────────────────┘");
        $this->newLine();
    }
}
