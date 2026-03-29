<?php

namespace Modules\Order\Console\Commands;

use Illuminate\Console\Command;
use Modules\Order\Models\Order;

class OrderStatsCommand extends Command
{
    protected $signature = 'order:stats';

    protected $description = 'Display order statistics';

    public function handle(): int
    {
        $this->info('Order Statistics');
        $this->line('-----------------');

        $total = Order::count();
        $pending = Order::where('status', 'pending')->count();
        $confirmed = Order::where('status', 'confirmed')->count();
        $processing = Order::where('status', 'processing')->count();
        $shipped = Order::where('status', 'shipped')->count();
        $delivered = Order::where('status', 'delivered')->count();
        $cancelled = Order::where('status', 'cancelled')->count();
        $refunded = Order::where('status', 'refunded')->count();

        $thisMonth = Order::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $thisWeek = Order::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();
        $today = Order::whereDate('created_at', now()->toDateString())->count();

        $this->newLine();
        $this->info('Order Status Overview');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Total Orders', $total, '100%'],
                ['Pending', $pending, $this->percentage($pending, $total)],
                ['Confirmed', $confirmed, $this->percentage($confirmed, $total)],
                ['Processing', $processing, $this->percentage($processing, $total)],
                ['Shipped', $shipped, $this->percentage($shipped, $total)],
                ['Delivered', $delivered, $this->percentage($delivered, $total)],
                ['Cancelled', $cancelled, $this->percentage($cancelled, $total)],
                ['Refunded', $refunded, $this->percentage($refunded, $total)],
            ]
        );

        // Payment stats
        $paymentPending = Order::where('payment_status', 'pending')->count();
        $paymentPaid = Order::where('payment_status', 'paid')->count();
        $paymentFailed = Order::where('payment_status', 'failed')->count();
        $paymentRefunded = Order::where('payment_status', 'refunded')->count();

        $this->newLine();
        $this->info('Payment Status');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Pending', $paymentPending, $this->percentage($paymentPending, $total)],
                ['Paid', $paymentPaid, $this->percentage($paymentPaid, $total)],
                ['Failed', $paymentFailed, $this->percentage($paymentFailed, $total)],
                ['Refunded', $paymentRefunded, $this->percentage($paymentRefunded, $total)],
            ]
        );

        // Revenue
        $totalRevenue = Order::where('payment_status', 'paid')->sum('total_amount');
        $monthlyRevenue = Order::where('payment_status', 'paid')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_amount');
        $avgOrderValue = $total > 0 ? Order::avg('total_amount') : 0;

        $this->newLine();
        $this->info('Revenue');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Revenue', '$' . number_format($totalRevenue, 2)],
                ['Monthly Revenue', '$' . number_format($monthlyRevenue, 2)],
                ['Average Order Value', '$' . number_format($avgOrderValue, 2)],
            ]
        );

        $this->newLine();
        $this->info('Growth');
        $this->table(
            ['Period', 'Orders'],
            [
                ['Today', $today],
                ['This Week', $thisWeek],
                ['This Month', $thisMonth],
            ]
        );

        // Top payment methods
        $byPaymentMethod = Order::selectRaw('payment_method, COUNT(*) as count')
            ->whereNotNull('payment_method')
            ->groupBy('payment_method')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'payment_method')
            ->toArray();

        if (!empty($byPaymentMethod)) {
            $this->newLine();
            $this->info('Top Payment Methods');
            $methodRows = collect($byPaymentMethod)->map(function ($count, $method) {
                return [ucfirst($method), $count];
            })->values()->toArray();
            $this->table(['Method', 'Count'], $methodRows);
        }

        return Command::SUCCESS;
    }

    protected function percentage(int $count, int $total): string
    {
        return $total > 0 ? round(($count / $total) * 100, 1) . '%' : '0%';
    }
}
