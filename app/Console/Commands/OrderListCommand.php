<?php

namespace Modules\Order\Console\Commands;

use Illuminate\Console\Command;
use Modules\Order\Models\Order;

class OrderListCommand extends Command
{
    protected $signature = 'order:list
                            {--status= : Filter by status}
                            {--payment= : Filter by payment status}
                            {--method= : Filter by payment method}
                            {--outlet= : Filter by outlet ID}
                            {--customer= : Filter by customer ID}
                            {--limit=10 : Number of orders to show}
                            {--recent : Show most recent orders}';

    protected $description = 'List orders';

    public function handle(): int
    {
        $this->info('Order List');
        $this->line('-----------');

        $query = Order::with(['customer', 'outlet'])->withCount('items');

        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        if ($payment = $this->option('payment')) {
            $query->where('payment_status', $payment);
        }

        if ($method = $this->option('method')) {
            $query->where('payment_method', $method);
        }

        if ($outlet = $this->option('outlet')) {
            $query->where('outlet_id', $outlet);
        }

        if ($customer = $this->option('customer')) {
            $query->where('customer_id', $customer);
        }

        if ($this->option('recent')) {
            $query->latest();
        } else {
            $query->orderBy('id', 'desc');
        }

        $limit = (int) $this->option('limit');
        $orders = $query->limit($limit)->get();

        if ($orders->isEmpty()) {
            $this->warn('No orders found.');
            return Command::SUCCESS;
        }

        $this->newLine();
        $this->table(
            ['ID', 'Order #', 'Cust ID', 'Customer', 'Outlet', 'Items', 'Subtotal', 'Tax', 'Total', 'Status', 'Payment', 'Method', 'Date'],
            $orders->map(fn ($order) => [
                $order->id,
                $order->order_number,
                $order->customer_id ?? '-',
                $order->customer?->name ?? 'Guest',
                $order->outlet?->name ?? '-',
                $order->items_count,
                '$' . number_format($order->subtotal, 2),
                '$' . number_format($order->tax_amount, 2),
                '$' . number_format($order->total_amount, 2),
                $order->status->value,
                $order->payment_status->value,
                $order->payment_method ?? '-',
                $order->created_at->format('Y-m-d H:i'),
            ])->toArray()
        );

        $this->newLine();
        $this->line("Showing {$orders->count()} of " . Order::count() . " total orders");

        // Show filter summary if any filters applied
        $filters = [];
        if ($this->option('status')) $filters[] = "status={$this->option('status')}";
        if ($this->option('payment')) $filters[] = "payment={$this->option('payment')}";
        if ($this->option('method')) $filters[] = "method={$this->option('method')}";
        if ($this->option('outlet')) $filters[] = "outlet={$this->option('outlet')}";
        if ($this->option('customer')) $filters[] = "customer={$this->option('customer')}";

        if (!empty($filters)) {
            $this->line("Filters: " . implode(', ', $filters));
        }

        return Command::SUCCESS;
    }
}
