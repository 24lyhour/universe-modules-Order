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

class OrderSimulateCommand extends Command
{
    protected $signature = 'order:simulate
                            {--customer= : Customer ID (or creates guest order)}
                            {--outlet= : Outlet ID}
                            {--payment-method=cash : Payment method}
                            {--items=* : Product IDs to order (e.g., --items=1 --items=2)}
                            {--interactive : Interactive step-by-step mode}';

    protected $description = 'Simulate a real customer order flow (browse → cart → checkout → order)';

    public function handle(): int
    {
        $this->info('🛒 Order Simulation - Customer Order Flow');
        $this->line('==========================================');
        $this->newLine();

        if ($this->option('interactive')) {
            return $this->runInteractive();
        }

        return $this->runFromOptions();
    }

    protected function runInteractive(): int
    {
        // Step 1: Select Customer
        $this->info('Step 1: Customer Selection');
        $this->line('---------------------------');

        $customers = Customer::limit(10)->get();
        if ($customers->isEmpty()) {
            $this->warn('No customers found. Creating as guest order.');
            $customer = null;
        } else {
            $this->table(
                ['ID', 'Name', 'Email', 'Phone'],
                $customers->map(fn ($c) => [$c->id, $c->name, $c->email ?? '-', $c->phone ?? '-'])->toArray()
            );

            $customerId = $this->ask('Enter Customer ID (or press Enter for guest)', '');
            $customer = $customerId ? Customer::find($customerId) : null;

            if ($customerId && !$customer) {
                $this->error("Customer ID {$customerId} not found.");
                return Command::FAILURE;
            }
        }

        $this->info($customer ? "✓ Customer: {$customer->name}" : "✓ Guest Order");
        $this->newLine();

        // Step 2: Select Outlet
        $this->info('Step 2: Select Outlet');
        $this->line('---------------------');

        $outlets = Outlet::limit(10)->get();
        if ($outlets->isEmpty()) {
            $this->error('No outlets found. Please create outlets first.');
            return Command::FAILURE;
        }

        $this->table(
            ['ID', 'Name', 'Address'],
            $outlets->map(fn ($o) => [$o->id, $o->name, Str::limit($o->address ?? '-', 30)])->toArray()
        );

        $outletId = $this->ask('Enter Outlet ID', $outlets->first()->id);
        $outlet = Outlet::find($outletId);

        if (!$outlet) {
            $this->error("Outlet ID {$outletId} not found.");
            return Command::FAILURE;
        }

        $this->info("✓ Outlet: {$outlet->name}");
        $this->newLine();

        // Step 3: Browse and Select Products
        $this->info('Step 3: Browse Products & Add to Cart');
        $this->line('--------------------------------------');

        $products = Product::where('status', 'active')
            ->where('outlet_id', $outlet->id)
            ->where('stock', '>', 0)
            ->limit(20)
            ->get();

        if ($products->isEmpty()) {
            // Try any active product
            $products = Product::where('status', 'active')
                ->where('stock', '>', 0)
                ->limit(20)
                ->get();
        }

        if ($products->isEmpty()) {
            $this->error('No active products found. Please create products first.');
            $this->line('Run: php artisan product:create --count=10');
            return Command::FAILURE;
        }

        $this->table(
            ['ID', 'Name', 'Price', 'Sale Price', 'Stock'],
            $products->map(fn ($p) => [
                $p->id,
                Str::limit($p->name, 25),
                '$' . number_format($p->price, 2),
                $p->sale_price ? '$' . number_format($p->sale_price, 2) : '-',
                $p->stock,
            ])->toArray()
        );

        $cartItems = [];
        $addMore = true;

        while ($addMore) {
            $productId = $this->ask('Enter Product ID to add');
            $product = $products->firstWhere('id', $productId);

            if (!$product) {
                $this->warn("Product ID {$productId} not found in list.");
                continue;
            }

            $quantity = (int) $this->ask("Quantity for {$product->name}?", 1);

            if ($quantity > $product->stock) {
                $this->warn("Only {$product->stock} in stock. Setting quantity to {$product->stock}.");
                $quantity = $product->stock;
            }

            $cartItems[] = [
                'product' => $product,
                'quantity' => $quantity,
            ];

            $unitPrice = $product->sale_price ?? $product->price;
            $this->info("✓ Added: {$product->name} x {$quantity} = $" . number_format($unitPrice * $quantity, 2));

            $addMore = $this->confirm('Add more items?', true);
        }

        if (empty($cartItems)) {
            $this->error('No items in cart. Order cancelled.');
            return Command::FAILURE;
        }

        $this->newLine();

        // Step 4: Create Cart
        $this->info('Step 4: Creating Cart...');
        $this->line('------------------------');

        $cart = $this->createCart($customer, $outlet, $cartItems);

        $this->info("✓ Cart created: {$cart->uuid}");
        $this->newLine();

        // Show cart summary
        $this->showCartSummary($cart);

        // Step 5: Checkout
        $this->info('Step 5: Checkout');
        $this->line('----------------');

        $paymentMethods = PaymentMethodEnum::cases();
        $this->line('Available payment methods:');
        foreach ($paymentMethods as $method) {
            $this->line("  - {$method->value}: {$method->label()}");
        }

        $paymentMethod = $this->choice(
            'Select payment method',
            array_column($paymentMethods, 'value'),
            'cash'
        );

        $notes = $this->ask('Order notes (optional)', '');

        if (!$this->confirm('Confirm order?', true)) {
            $this->warn('Order cancelled.');
            $cart->update(['status' => CartStatusEnum::Abandoned->value]);
            return Command::SUCCESS;
        }

        // Step 6: Convert Cart to Order
        $this->info('Step 6: Processing Order...');
        $this->line('---------------------------');

        $order = $this->convertCartToOrder($cart, $paymentMethod, $notes);

        $this->newLine();
        $this->info('🎉 ORDER PLACED SUCCESSFULLY!');
        $this->line('=============================');
        $this->newLine();

        $this->showOrderSummary($order);

        return Command::SUCCESS;
    }

    protected function runFromOptions(): int
    {
        $customerId = $this->option('customer');
        $outletId = $this->option('outlet');
        $paymentMethod = $this->option('payment-method');
        $productIds = $this->option('items');

        // Get customer
        $customer = $customerId ? Customer::find($customerId) : null;

        // Get outlet
        $outlet = $outletId
            ? Outlet::find($outletId)
            : Outlet::inRandomOrder()->first();

        if (!$outlet) {
            $this->error('No outlet found.');
            return Command::FAILURE;
        }

        // Get products
        if (empty($productIds)) {
            $products = Product::where('status', 'active')
                ->where('stock', '>', 0)
                ->inRandomOrder()
                ->limit(3)
                ->get();
        } else {
            $products = Product::whereIn('id', $productIds)
                ->where('status', 'active')
                ->get();
        }

        if ($products->isEmpty()) {
            $this->error('No active products found.');
            return Command::FAILURE;
        }

        // Build cart items
        $cartItems = $products->map(fn ($p) => [
            'product' => $p,
            'quantity' => rand(1, 3),
        ])->toArray();

        $this->info("Customer: " . ($customer?->name ?? 'Guest'));
        $this->info("Outlet: {$outlet->name}");
        $this->info("Items: " . count($cartItems));
        $this->newLine();

        // Create cart
        $cart = $this->createCart($customer, $outlet, $cartItems);
        $this->showCartSummary($cart);

        // Convert to order
        $order = $this->convertCartToOrder($cart, $paymentMethod, 'Order via CLI simulation');

        $this->newLine();
        $this->info('🎉 ORDER PLACED SUCCESSFULLY!');
        $this->newLine();

        $this->showOrderSummary($order);

        return Command::SUCCESS;
    }

    protected function createCart(?Customer $customer, Outlet $outlet, array $cartItems): Cart
    {
        $cart = Cart::create([
            'uuid' => Str::uuid(),
            'customer_id' => $customer?->id,
            'outlet_id' => $outlet->id,
            'status' => CartStatusEnum::Active->value,
            'is_active' => true,
            'notes' => 'Cart created via order simulation',
            'expires_at' => now()->addHours(2),
        ]);

        foreach ($cartItems as $item) {
            $product = $item['product'];
            $quantity = $item['quantity'];
            $unitPrice = $product->sale_price ?? $product->price;

            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $unitPrice * $quantity,
            ]);
        }

        return $cart->fresh(['customer', 'outlet', 'items.product']);
    }

    protected function convertCartToOrder(Cart $cart, string $paymentMethod, string $notes = ''): Order
    {
        $subtotal = $cart->items->sum('total_amount');
        $discountAmount = 0;
        $taxRate = 0.10; // 10% tax
        $taxAmount = $subtotal * $taxRate;
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        // Create order
        $order = Order::create([
            'uuid' => Str::uuid(),
            'order_number' => 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6)),
            'customer_id' => $cart->customer_id,
            'outlet_id' => $cart->outlet_id,
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => OrderStatusEnum::Pending->value,
            'payment_status' => PaymentStatusEnum::Pending->value,
            'payment_method' => $paymentMethod,
            'notes' => $notes,
        ]);

        // Copy cart items to order items
        foreach ($cart->items as $cartItem) {
            $order->items()->create([
                'product_id' => $cartItem->product_id,
                'product_name' => $cartItem->product?->name ?? 'Unknown Product',
                'product_sku' => $cartItem->product?->sku,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->unit_price,
                'discount_amount' => 0,
                'total_amount' => $cartItem->total_amount,
            ]);

            // Decrease product stock
            if ($cartItem->product) {
                $cartItem->product->decrement('stock', $cartItem->quantity);
            }
        }

        // Mark cart as converted
        $cart->update([
            'status' => CartStatusEnum::Converted->value,
            'is_active' => false,
        ]);

        return $order->fresh(['customer', 'outlet', 'items']);
    }

    protected function showCartSummary(Cart $cart): void
    {
        $this->info('Cart Summary:');
        $this->table(
            ['Product', 'Qty', 'Unit Price', 'Total'],
            $cart->items->map(fn ($item) => [
                Str::limit($item->product?->name ?? 'Unknown', 30),
                $item->quantity,
                '$' . number_format($item->unit_price, 2),
                '$' . number_format($item->total_amount, 2),
            ])->toArray()
        );

        $subtotal = $cart->items->sum('total_amount');
        $this->line("Subtotal: $" . number_format($subtotal, 2));
        $this->newLine();
    }

    protected function showOrderSummary(Order $order): void
    {
        $this->table(
            ['Field', 'Value'],
            [
                ['Order Number', $order->order_number],
                ['Customer', $order->customer?->name ?? 'Guest'],
                ['Customer ID', $order->customer_id ?? '-'],
                ['Outlet', $order->outlet?->name ?? '-'],
                ['Items', $order->items->count()],
                ['Subtotal', '$' . number_format($order->subtotal, 2)],
                ['Discount', '$' . number_format($order->discount_amount, 2)],
                ['Tax (10%)', '$' . number_format($order->tax_amount, 2)],
                ['Total', '$' . number_format($order->total_amount, 2)],
                ['Status', $order->status->value],
                ['Payment Status', $order->payment_status->value],
                ['Payment Method', $order->payment_method],
                ['Created At', $order->created_at->format('Y-m-d H:i:s')],
            ]
        );

        $this->newLine();
        $this->info('Order Items:');
        $this->table(
            ['Product', 'SKU', 'Qty', 'Unit Price', 'Total'],
            $order->items->map(fn ($item) => [
                Str::limit($item->product_name, 25),
                $item->product_sku ?? '-',
                $item->quantity,
                '$' . number_format($item->unit_price, 2),
                '$' . number_format($item->total_amount, 2),
            ])->toArray()
        );
    }
}
