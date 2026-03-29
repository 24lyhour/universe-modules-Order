<?php

namespace Modules\Order\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\Order\Models\Cart;

class CartListCommand extends Command
{
    protected $signature = 'cart:list
                            {--status= : Filter by status}
                            {--outlet= : Filter by outlet ID}
                            {--customer= : Filter by customer ID}
                            {--active : Show only active carts}
                            {--expired : Show only expired carts}
                            {--limit=10 : Number of carts to show}';

    protected $description = 'List carts';

    public function handle(): int
    {
        $this->info('Cart List');
        $this->line('----------');

        $query = Cart::with(['customer', 'outlet'])->withCount('items');

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        if ($outlet = $this->option('outlet')) {
            $query->where('outlet_id', $outlet);
        }

        if ($customer = $this->option('customer')) {
            $query->where('customer_id', $customer);
        }

        if ($this->option('active')) {
            $query->where('is_active', true);
        }

        if ($this->option('expired')) {
            $query->where('expires_at', '<', now());
        }

        $query->orderBy('id', 'desc');

        $limit = (int) $this->option('limit');
        $carts = $query->limit($limit)->get();

        if ($carts->isEmpty()) {
            $this->warn('No carts found.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['ID', 'UUID', 'Cust ID', 'Customer', 'Outlet', 'Items', 'Status', 'Active', 'Expires', 'Created'],
            $carts->map(fn ($cart) => [
                $cart->id,
                Str::limit($cart->uuid, 8, '...'),
                $cart->customer_id ?? '-',
                $cart->customer?->name ?? 'Guest',
                $cart->outlet?->name ?? '-',
                $cart->items_count,
                $cart->status->value,
                $cart->is_active ? 'Yes' : 'No',
                $cart->expires_at?->format('Y-m-d H:i') ?? '-',
                $cart->created_at->format('Y-m-d H:i'),
            ])->toArray()
        );

        $this->newLine();
        $this->line("Showing {$carts->count()} of " . Cart::count() . " total carts");

        // Show filter summary if any filters applied
        $filters = [];
        if ($this->option('status')) {
            $filters[] = "status={$this->option('status')}";
        }
        if ($this->option('outlet')) {
            $filters[] = "outlet={$this->option('outlet')}";
        }
        if ($this->option('customer')) {
            $filters[] = "customer={$this->option('customer')}";
        }
        if ($this->option('active')) {
            $filters[] = "active=true";
        }
        if ($this->option('expired')) {
            $filters[] = "expired=true";
        }

        if (!empty($filters)) {
            $this->line("Filters: " . implode(', ', $filters));
        }

        // Show stats
        $this->newLine();
        $this->info('Quick Stats:');
        $this->line("  Active: " . Cart::where('is_active', true)->count());
        $this->line("  Inactive: " . Cart::where('is_active', false)->count());
        $this->line("  Expired: " . Cart::where('expires_at', '<', now())->count());

        return Command::SUCCESS;
    }
}
