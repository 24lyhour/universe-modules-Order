<?php

namespace Modules\Order\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Enums\PaymentMethodEnum;
use Modules\Order\Enums\PaymentStatusEnum;
use Modules\Order\Models\Order;
use Modules\Outlet\Models\Outlet;
use Modules\Product\Models\Product;

class OrderCreateCommand extends Command
{
    protected $signature = 'order:create
                            {--count=1 : Number of orders to create}
                            {--customer= : Customer ID}
                            {--outlet= : Outlet ID}
                            {--status= : Order status}
                            {--payment-method= : Payment method}
                            {--payment-status= : Payment status}
                            {--items=3 : Number of items per order}
                            {--interactive : Interactive mode}';

    protected $description = 'Create test orders for development/testing';

    public function handle(): int
    {
        $this->info('Create Test Orders');
        $this->line('------------------');

        if ($this->option('interactive')) {
            return $this->createInteractive();
        }

        return $this->createFromOptions();
    }

    protected function createInteractive(): int
    {
        $count = (int) $this->ask('How many orders to create?', 1);

        $customers = Customer::pluck('name', 'id')->toArray();
        $outlets = Outlet::pluck('name', 'id')->toArray();

        $customerId = null;
        if (!empty($customers)) {
            $customerChoice = $this->choice(
                'Select customer (or skip for random)',
                ['random' => 'Random Customer'] + $customers,
                'random'
            );
            $customerId = $customerChoice !== 'random' ? array_search($customerChoice, $customers) : null;
        }

        $outletId = null;
        if (!empty($outlets)) {
            $outletChoice = $this->choice(
                'Select outlet (or skip for random)',
                ['random' => 'Random Outlet'] + $outlets,
                'random'
            );
            $outletId = $outletChoice !== 'random' ? array_search($outletChoice, $outlets) : null;
        }

        $status = $this->choice(
            'Order status',
            array_combine(OrderStatusEnum::values(), OrderStatusEnum::values()),
            'pending'
        );

        $paymentMethod = $this->choice(
            'Payment method',
            ['random' => 'Random'] + array_combine(PaymentMethodEnum::values(), PaymentMethodEnum::values()),
            'random'
        );
        $paymentMethod = $paymentMethod === 'random' ? null : $paymentMethod;

        $paymentStatus = $this->choice(
            'Payment status',
            ['auto' => 'Auto (based on order status)'] + array_combine(PaymentStatusEnum::values(), PaymentStatusEnum::values()),
            'auto'
        );
        $paymentStatus = $paymentStatus === 'auto' ? null : $paymentStatus;

        $itemsCount = (int) $this->ask('Items per order?', 3);

        return $this->createOrders($count, $customerId, $outletId, $status, $paymentMethod, $paymentStatus, $itemsCount);
    }

    protected function createFromOptions(): int
    {
        $count = (int) $this->option('count');
        $customerId = $this->option('customer');
        $outletId = $this->option('outlet');
        $status = $this->option('status') ?? 'pending';
        $paymentMethod = $this->option('payment-method');
        $paymentStatus = $this->option('payment-status');
        $itemsCount = (int) $this->option('items');

        return $this->createOrders($count, $customerId, $outletId, $status, $paymentMethod, $paymentStatus, $itemsCount);
    }

    protected function createOrders(int $count, ?int $customerId, ?int $outletId, string $status, ?string $paymentMethod, ?string $paymentStatus, int $itemsCount): int
    {
        $this->newLine();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $orders = [];
        $products = Product::where('status', 'active')->get();

        if ($products->isEmpty()) {
            $this->error('No active products found. Please create products first.');
            return Command::FAILURE;
        }

        for ($i = 0; $i < $count; $i++) {
            $order = $this->createSingleOrder($customerId, $outletId, $status, $paymentMethod, $paymentStatus, $itemsCount, $products);
            $orders[] = $order;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Created {$count} test order(s) successfully!");
        $this->newLine();

        // Show summary table
        $this->info('Orders Summary:');
        $this->table(
            ['Order #', 'Customer ID', 'Customer Name', 'Outlet', 'Items', 'Subtotal', 'Tax', 'Total', 'Status', 'Payment', 'Method'],
            collect($orders)->map(fn ($order) => [
                $order->order_number,
                $order->customer_id ?? '-',
                $order->customer?->name ?? 'Guest',
                $order->outlet?->name ?? '-',
                $order->items->count(),
                '$' . number_format($order->subtotal, 2),
                '$' . number_format($order->tax_amount, 2),
                '$' . number_format($order->total_amount, 2),
                $order->status->value,
                $order->payment_status->value,
                $order->payment_method ?? '-',
            ])->toArray()
        );

        // Show detailed items for each order
        $this->newLine();
        $this->info('Order Items Detail:');
        foreach ($orders as $order) {
            $this->newLine();
            $this->line("Order: {$order->order_number}");
            $this->table(
                ['Product', 'SKU', 'Qty', 'Unit Price', 'Total'],
                $order->items->map(fn ($item) => [
                    $item->product_name,
                    $item->product_sku ?? '-',
                    $item->quantity,
                    '$' . number_format($item->unit_price, 2),
                    '$' . number_format($item->total_amount, 2),
                ])->toArray()
            );
        }

        return Command::SUCCESS;
    }

    protected function createSingleOrder(?int $customerId, ?int $outletId, string $status, ?string $paymentMethod, ?string $paymentStatus, int $itemsCount, $products): Order
    {
        // Get random customer/outlet if not specified
        $customer = $customerId
            ? Customer::find($customerId)
            : Customer::inRandomOrder()->first();

        $outlet = $outletId
            ? Outlet::find($outletId)
            : Outlet::inRandomOrder()->first();

        // Determine payment method and status
        $finalPaymentMethod = $paymentMethod ?? $this->getRandomPaymentMethod();
        $finalPaymentStatus = $paymentStatus ?? $this->getRandomPaymentStatus($status);

        // Create order
        $order = Order::create([
            'uuid' => Str::uuid(),
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_id' => $customer?->id,
            'outlet_id' => $outlet?->id,
            'subtotal' => 0,
            'discount_amount' => rand(0, 20),
            'tax_amount' => 0,
            'total_amount' => 0,
            'status' => $status,
            'payment_status' => $finalPaymentStatus,
            'payment_method' => $finalPaymentMethod,
            'notes' => 'Test order created via CLI',
        ]);

        // Add items
        $subtotal = 0;
        $selectedProducts = $products->random(min($itemsCount, $products->count()));

        foreach ($selectedProducts as $product) {
            $quantity = rand(1, 5);
            $unitPrice = $product->sale_price ?? $product->price ?? rand(10, 100);
            $itemTotal = $quantity * $unitPrice;
            $subtotal += $itemTotal;

            $order->items()->create([
                'product_id' => $product->id,
                'product_name' => $product->name,
                'product_sku' => $product->sku,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_amount' => 0,
                'total_amount' => $itemTotal,
            ]);
        }

        // Update totals
        $taxAmount = $subtotal * 0.1; // 10% tax
        $totalAmount = $subtotal - $order->discount_amount + $taxAmount;

        $order->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
        ]);

        return $order->fresh(['customer', 'outlet', 'items']);
    }

    protected function getRandomPaymentStatus(string $orderStatus): string
    {
        return match ($orderStatus) {
            'delivered', 'shipped' => 'paid',
            'cancelled', 'refunded' => 'refunded',
            default => collect(['pending', 'paid'])->random(),
        };
    }

    protected function getRandomPaymentMethod(): string
    {
        return collect(PaymentMethodEnum::values())->random();
    }
}
