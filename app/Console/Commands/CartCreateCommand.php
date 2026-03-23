<?php

namespace Modules\Order\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\CartStatusEnum;
use Modules\Order\Models\Cart;
use Modules\Outlet\Models\Outlet;
use Modules\Product\Models\Product;

class CartCreateCommand extends Command
{
    protected $signature = 'cart:create
                            {--count=1 : Number of carts to create}
                            {--customer= : Customer ID}
                            {--outlet= : Outlet ID}
                            {--status=active : Cart status}
                            {--items=3 : Number of items per cart}
                            {--interactive : Interactive mode}';

    protected $description = 'Create test carts for development/testing';

    public function handle(): int
    {
        $this->info('Create Test Carts');
        $this->line('-----------------');

        if ($this->option('interactive')) {
            return $this->createInteractive();
        }

        return $this->createFromOptions();
    }

    protected function createInteractive(): int
    {
        $count = (int) $this->ask('How many carts to create?', 1);

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
            'Cart status',
            array_combine(CartStatusEnum::values(), CartStatusEnum::values()),
            'active'
        );

        $itemsCount = (int) $this->ask('Items per cart?', 3);

        return $this->createCarts($count, $customerId, $outletId, $status, $itemsCount);
    }

    protected function createFromOptions(): int
    {
        $count = (int) $this->option('count');
        $customerId = $this->option('customer');
        $outletId = $this->option('outlet');
        $status = $this->option('status') ?? 'active';
        $itemsCount = (int) $this->option('items');

        return $this->createCarts($count, $customerId, $outletId, $status, $itemsCount);
    }

    protected function createCarts(int $count, ?int $customerId, ?int $outletId, string $status, int $itemsCount): int
    {
        $this->newLine();
        $bar = $this->output->createProgressBar($count);
        $bar->start();

        $carts = [];
        $products = Product::where('status', 'active')->get();

        if ($products->isEmpty()) {
            $this->error('No active products found. Please create products first.');
            return Command::FAILURE;
        }

        for ($i = 0; $i < $count; $i++) {
            $cart = $this->createSingleCart($customerId, $outletId, $status, $itemsCount, $products);
            $carts[] = $cart;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Created {$count} test cart(s) successfully!");
        $this->newLine();

        // Show summary table
        $this->info('Carts Summary:');
        $this->table(
            ['UUID', 'Customer ID', 'Customer Name', 'Outlet', 'Items', 'Total', 'Status', 'Active', 'Expires'],
            collect($carts)->map(fn ($cart) => [
                Str::limit($cart->uuid, 8, '...'),
                $cart->customer_id ?? '-',
                $cart->customer?->name ?? 'Guest',
                $cart->outlet?->name ?? '-',
                $cart->items->count(),
                '$' . number_format($cart->items->sum('total_amount'), 2),
                $cart->status->value,
                $cart->is_active ? 'Yes' : 'No',
                $cart->expires_at?->format('Y-m-d H:i') ?? '-',
            ])->toArray()
        );

        // Show detailed items for each cart
        $this->newLine();
        $this->info('Cart Items Detail:');
        foreach ($carts as $cart) {
            $this->newLine();
            $this->line("Cart: " . Str::limit($cart->uuid, 8, '...'));
            $this->table(
                ['Product ID', 'Product Name', 'Qty', 'Unit Price', 'Total'],
                $cart->items->map(fn ($item) => [
                    $item->product_id,
                    $item->product?->name ?? 'Unknown',
                    $item->quantity,
                    '$' . number_format($item->unit_price, 2),
                    '$' . number_format($item->total_amount, 2),
                ])->toArray()
            );
        }

        return Command::SUCCESS;
    }

    protected function createSingleCart(?int $customerId, ?int $outletId, string $status, int $itemsCount, $products): Cart
    {
        // Get random customer/outlet if not specified
        $customer = $customerId
            ? Customer::find($customerId)
            : Customer::inRandomOrder()->first();

        $outlet = $outletId
            ? Outlet::find($outletId)
            : Outlet::inRandomOrder()->first();

        // Create cart
        $cart = Cart::create([
            'uuid' => Str::uuid(),
            'customer_id' => $customer?->id,
            'outlet_id' => $outlet?->id,
            'status' => $status,
            'is_active' => $status === 'active',
            'notes' => 'Test cart created via CLI',
            'expires_at' => $status === 'active' ? now()->addDays(7) : null,
        ]);

        // Add items
        $selectedProducts = $products->random(min($itemsCount, $products->count()));

        foreach ($selectedProducts as $product) {
            $quantity = rand(1, 5);
            $unitPrice = $product->sale_price ?? $product->price ?? rand(10, 100);
            $itemTotal = $quantity * $unitPrice;

            $cart->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_amount' => $itemTotal,
            ]);
        }

        return $cart->fresh(['customer', 'outlet', 'items.product']);
    }
}
