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
use Modules\Outlet\Models\Outlet;
use Modules\Product\Models\Product;

class OrderSeeder extends Seeder
{
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
                $itemDiscount = fake()->boolean(10) ? fake()->randomFloat(2, 0, 5) : 0;
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
        }

        $this->command->info('OrderSeeder: Created ' . Order::count() . ' orders with items.');
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
        ];

        $baseDate = \Carbon\Carbon::parse($createdAt);

        switch ($status) {
            case OrderStatusEnum::Delivering:
                $timestamps['shipped_at'] = $baseDate->copy()->addHours(rand(1, 4));
                break;

            case OrderStatusEnum::Delivered:
            case OrderStatusEnum::Completed:
                $timestamps['shipped_at'] = $baseDate->copy()->addHours(rand(1, 3));
                $timestamps['delivered_at'] = $baseDate->copy()->addHours(rand(4, 8));
                break;

            case OrderStatusEnum::Cancelled:
                $timestamps['cancelled_at'] = $baseDate->copy()->addMinutes(rand(10, 120));
                break;

            case OrderStatusEnum::Refunded:
                $timestamps['shipped_at'] = $baseDate->copy()->addHours(rand(1, 3));
                $timestamps['delivered_at'] = $baseDate->copy()->addHours(rand(4, 8));
                break;
        }

        return $timestamps;
    }
}
