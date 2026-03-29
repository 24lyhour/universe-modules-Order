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

        $cart->update(['status' => CartStatusEnum::Converted->value, 'is_active' => false]);

        return $order->fresh(['customer', 'outlet', 'items']);
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

        // Outlet
        $this->line("│  🏪 Outlet: {$order->outlet?->name} (ID: {$order->outlet_id})");
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
        $this->line("│     Tax 10%: \$" . number_format($order->tax_amount, 2));
        $this->line("│     <fg=green>TOTAL: \$" . number_format($order->total_amount, 2) . "</>");
        $this->line("├─────────────────────────────────────────────────────┤");
        $this->line("│  Payment: {$order->payment_method} ({$order->payment_status->value})");
        $this->line("│  Status: {$order->status->value}");
        $this->line("└─────────────────────────────────────────────────────┘");
        $this->newLine();
    }
}
