<?php

namespace Modules\Order\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Enums\PaymentMethodEnum;
use Modules\Order\Enums\PaymentStatusEnum;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;
use Modules\Order\Models\OrderShipping;
use Modules\Outlet\Models\Outlet;
use Modules\Product\Models\Product;

class OrderSeeder extends Seeder
{
    /**
     * Shipping carriers with their methods.
     */
    private array $carriers = [
        'DHL Express' => ['express', 'standard', 'same_day'],
        'FedEx' => ['priority', 'express', 'ground'],
        'UPS' => ['next_day', 'express', 'standard'],
        'Local Delivery' => ['motorcycle', 'car', 'truck'],
        'Cambodia Post' => ['standard', 'registered'],
    ];

    /**
     * Cambodia cities with GPS coordinates (Phnom Penh area).
     */
    private array $locations = [
        ['city' => 'Phnom Penh', 'state' => 'Phnom Penh', 'lat' => 11.5564, 'lng' => 104.9282],
        ['city' => 'Sen Sok', 'state' => 'Phnom Penh', 'lat' => 11.5869, 'lng' => 104.8636],
        ['city' => 'Chamkarmon', 'state' => 'Phnom Penh', 'lat' => 11.5449, 'lng' => 104.9282],
        ['city' => 'Toul Kork', 'state' => 'Phnom Penh', 'lat' => 11.5780, 'lng' => 104.8999],
        ['city' => 'Meanchey', 'state' => 'Phnom Penh', 'lat' => 11.5200, 'lng' => 104.8900],
        ['city' => 'Russey Keo', 'state' => 'Phnom Penh', 'lat' => 11.6100, 'lng' => 104.8800],
        ['city' => 'Siem Reap', 'state' => 'Siem Reap', 'lat' => 13.3671, 'lng' => 103.8448],
        ['city' => 'Battambang', 'state' => 'Battambang', 'lat' => 13.0957, 'lng' => 103.2022],
        ['city' => 'Sihanoukville', 'state' => 'Preah Sihanouk', 'lat' => 10.6093, 'lng' => 103.5296],
        ['city' => 'Kampong Cham', 'state' => 'Kampong Cham', 'lat' => 11.9962, 'lng' => 105.4635],
    ];

    /**
     * Street names for Cambodia.
     */
    private array $streets = [
        'Street 271', 'Street 63', 'Monivong Boulevard', 'Norodom Boulevard',
        'Sothearos Boulevard', 'Street 310', 'Street 178', 'Mao Tse Toung Boulevard',
        'Russian Boulevard', 'Kampuchea Krom Boulevard', 'Street 2004', 'Street 1986',
        'Preah Sihanouk Boulevard', 'Street 95', 'Street 130', 'Street 240',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = Customer::all();
        $outlets = Outlet::all();
        $products = Product::all();

        if ($customers->isEmpty() || $outlets->isEmpty() || $products->isEmpty()) {
            $this->command->warn('Skipping OrderSeeder: Missing customers, outlets, or products.');
            return;
        }

        // Create orders with different statuses for realistic data
        $orderStatuses = [
            ['status' => OrderStatusEnum::Pending, 'payment' => PaymentStatusEnum::Pending, 'weight' => 15],
            ['status' => OrderStatusEnum::Confirmed, 'payment' => PaymentStatusEnum::Paid, 'weight' => 10],
            ['status' => OrderStatusEnum::Preparing, 'payment' => PaymentStatusEnum::Paid, 'weight' => 12],
            ['status' => OrderStatusEnum::Ready, 'payment' => PaymentStatusEnum::Paid, 'weight' => 8],
            ['status' => OrderStatusEnum::Delivering, 'payment' => PaymentStatusEnum::Paid, 'weight' => 10],
            ['status' => OrderStatusEnum::Delivered, 'payment' => PaymentStatusEnum::Paid, 'weight' => 20],
            ['status' => OrderStatusEnum::Completed, 'payment' => PaymentStatusEnum::Paid, 'weight' => 15],
            ['status' => OrderStatusEnum::Cancelled, 'payment' => PaymentStatusEnum::Refunded, 'weight' => 5],
            ['status' => OrderStatusEnum::Refunded, 'payment' => PaymentStatusEnum::Refunded, 'weight' => 5],
        ];

        $paymentMethods = PaymentMethodEnum::cases();
        $shippingCount = 0;

        // Create 50 orders
        for ($i = 0; $i < 50; $i++) {
            $customer = $customers->random();
            $outlet = $outlets->random();

            // Weighted random status selection
            $statusConfig = $this->weightedRandom($orderStatuses);
            $orderStatus = $statusConfig['status'];
            $paymentStatus = $statusConfig['payment'];
            $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

            // Calculate dates based on status
            $createdAt = fake()->dateTimeBetween('-30 days', 'now');
            $timestamps = $this->calculateTimestamps($orderStatus, $createdAt);

            $discountAmount = fake()->boolean(30) ? fake()->randomFloat(2, 0, 10) : 0;

            $order = Order::create([
                'uuid' => Str::uuid(),
                'order_number' => Order::generateOrderNumber(),
                'customer_id' => $customer->id,
                'outlet_id' => $outlet->id,
                'cart_id' => null,
                'subtotal' => 0,
                'discount_amount' => $discountAmount,
                'tax_amount' => 0,
                'total_amount' => 0,
                'status' => $orderStatus,
                'payment_status' => $paymentStatus,
                'payment_method' => $paymentMethod->value,
                'notes' => fake()->optional(0.4)->sentence(),
                'shipped_at' => $timestamps['shipped_at'],
                'delivered_at' => $timestamps['delivered_at'],
                'cancelled_at' => $timestamps['cancelled_at'],
                'completed_at' => $timestamps['completed_at'],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Add 1-6 items to the order
            $itemCount = rand(1, 6);
            $selectedProducts = $products->random(min($itemCount, $products->count()));
            $subtotal = 0;

            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 4);
                $unitPrice = $product->price ?? fake()->randomFloat(2, 2, 30);
                $itemDiscount = fake()->boolean(40) ? fake()->randomFloat(2, 1, 8) : 0;
                $totalAmount = ($unitPrice * $quantity) - $itemDiscount;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_sku' => $product->sku ?? 'SKU-' . Str::upper(Str::random(6)),
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount_amount' => $itemDiscount,
                    'total_amount' => $totalAmount,
                    'notes' => fake()->optional(0.15)->sentence(),
                ]);

                $subtotal += $totalAmount;
            }

            // Update order totals
            $taxAmount = $subtotal * 0.1; // 10% tax
            $order->update([
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $subtotal + $taxAmount - ($order->discount_amount ?? 0),
            ]);

            // Create shipping record for ALL orders (so map route always shows)
            $this->createShipping($order, $customer, $timestamps);
            $shippingCount++;
        }

        $this->command->info('OrderSeeder: Created ' . Order::count() . ' orders with items and ' . $shippingCount . ' shipping records.');
    }

    /**
     * Determine if order should have shipping based on status.
     */
    private function shouldHaveShipping(OrderStatusEnum $status): bool
    {
        return in_array($status, [
            OrderStatusEnum::Ready,
            OrderStatusEnum::Delivering,
            OrderStatusEnum::Delivered,
            OrderStatusEnum::Completed,
            OrderStatusEnum::Refunded,
        ]);
    }

    /**
     * Create shipping record for an order.
     */
    private function createShipping(Order $order, Customer $customer, array $timestamps): void
    {
        $location = $this->locations[array_rand($this->locations)];
        $carrier = array_rand($this->carriers);
        $methods = $this->carriers[$carrier];
        $method = $methods[array_rand($methods)];

        // Add some randomness to GPS coordinates (within ~2km)
        $latOffset = (rand(-200, 200) / 10000);
        $lngOffset = (rand(-200, 200) / 10000);

        $baseDate = \Carbon\Carbon::parse($order->created_at);

        OrderShipping::create([
            'order_id' => $order->id,
            'carrier' => $carrier,
            'method' => $method,
            'shipping_cost' => fake()->randomFloat(2, 2, 15),
            'tracking_number' => $this->generateTrackingNumber($carrier),
            'recipient_name' => $customer->name,
            'phone' => $customer->phone ?? fake()->numerify('+855 ## ### ####'),
            'street_1' => $this->streets[array_rand($this->streets)] . ', #' . rand(1, 999),
            'street_2' => fake()->optional(0.3)->secondaryAddress(),
            'city' => $location['city'],
            'state' => $location['state'],
            'postal_code' => fake()->numerify('#####'),
            'country' => 'Cambodia',
            'latitude' => $location['lat'] + $latOffset,
            'longitude' => $location['lng'] + $lngOffset,
            'weight' => fake()->randomFloat(2, 0.5, 10),
            'notes' => fake()->optional(0.2)->sentence(),
            'estimated_delivery_at' => $baseDate->copy()->addDays(rand(1, 5)),
            'shipped_at' => $timestamps['shipped_at'],
            'delivered_at' => $timestamps['delivered_at'],
        ]);
    }

    /**
     * Generate tracking number based on carrier.
     */
    private function generateTrackingNumber(string $carrier): string
    {
        $prefix = match ($carrier) {
            'DHL Express' => 'DHL',
            'FedEx' => 'FX',
            'UPS' => '1Z',
            'Local Delivery' => 'LD',
            'Cambodia Post' => 'CP',
            default => 'TRK',
        };

        return $prefix . strtoupper(Str::random(4)) . rand(10000000, 99999999);
    }

    /**
     * Weighted random selection.
     */
    private function weightedRandom(array $items): array
    {
        $totalWeight = array_sum(array_column($items, 'weight'));
        $random = rand(1, $totalWeight);
        $current = 0;

        foreach ($items as $item) {
            $current += $item['weight'];
            if ($random <= $current) {
                return $item;
            }
        }

        return $items[0];
    }

    /**
     * Calculate timestamps based on order status.
     */
    private function calculateTimestamps(OrderStatusEnum $status, \DateTime $createdAt): array
    {
        $timestamps = [
            'shipped_at' => null,
            'delivered_at' => null,
            'cancelled_at' => null,
            'completed_at' => null,
        ];

        $baseDate = \Carbon\Carbon::parse($createdAt);

        switch ($status) {
            case OrderStatusEnum::Delivering:
                $timestamps['shipped_at'] = $baseDate->copy()->addHours(rand(1, 4));
                break;

            case OrderStatusEnum::Delivered:
                $timestamps['shipped_at'] = $baseDate->copy()->addHours(rand(1, 3));
                $timestamps['delivered_at'] = $baseDate->copy()->addHours(rand(4, 8));
                break;

            case OrderStatusEnum::Completed:
                $timestamps['shipped_at'] = $baseDate->copy()->addHours(rand(1, 3));
                $timestamps['delivered_at'] = $baseDate->copy()->addHours(rand(4, 8));
                $timestamps['completed_at'] = $baseDate->copy()->addHours(rand(9, 24));
                break;

            case OrderStatusEnum::Cancelled:
                $timestamps['cancelled_at'] = $baseDate->copy()->addMinutes(rand(10, 120));
                break;

            case OrderStatusEnum::Refunded:
                $timestamps['shipped_at'] = $baseDate->copy()->addHours(rand(1, 3));
                $timestamps['delivered_at'] = $baseDate->copy()->addHours(rand(4, 8));
                $timestamps['completed_at'] = $baseDate->copy()->addHours(rand(9, 24));
                break;
        }

        return $timestamps;
    }
}
