<?php

namespace Modules\Order\Console\Commands;

use Illuminate\Console\Command;
use Modules\Order\Models\Cart;

class CartStatsCommand extends Command
{
    protected $signature = 'cart:stats';

    protected $description = 'Display cart statistics';

    public function handle(): int
    {
        $this->info('Cart Statistics');
        $this->line('----------------');

        $total = Cart::count();
        $active = Cart::where('status', 'active')->count();
        $abandoned = Cart::where('status', 'abandoned')->count();
        $converted = Cart::where('status', 'converted')->count();
        $expired = Cart::where('status', 'expired')->count();

        $isActive = Cart::where('is_active', true)->count();
        $isInactive = Cart::where('is_active', false)->count();

        $thisMonth = Cart::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
        $thisWeek = Cart::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ])->count();
        $today = Cart::whereDate('created_at', now()->toDateString())->count();

        $this->newLine();
        $this->info('Cart Status Overview');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Total Carts', $total, '100%'],
                ['Active', $active, $this->percentage($active, $total)],
                ['Abandoned', $abandoned, $this->percentage($abandoned, $total)],
                ['Converted', $converted, $this->percentage($converted, $total)],
                ['Expired', $expired, $this->percentage($expired, $total)],
            ]
        );

        $this->newLine();
        $this->info('Activity Status');
        $this->table(
            ['Status', 'Count', 'Percentage'],
            [
                ['Active (is_active)', $isActive, $this->percentage($isActive, $total)],
                ['Inactive', $isInactive, $this->percentage($isInactive, $total)],
            ]
        );

        // Cart value
        $totalValue = Cart::with('items')->get()->sum(function ($cart) {
            return $cart->items->sum('total_amount');
        });
        $avgCartValue = $total > 0 ? $totalValue / $total : 0;
        $activeCartValue = Cart::where('status', 'active')->with('items')->get()->sum(function ($cart) {
            return $cart->items->sum('total_amount');
        });

        $this->newLine();
        $this->info('Cart Value');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Cart Value', '$' . number_format($totalValue, 2)],
                ['Active Cart Value', '$' . number_format($activeCartValue, 2)],
                ['Average Cart Value', '$' . number_format($avgCartValue, 2)],
            ]
        );

        $this->newLine();
        $this->info('Growth');
        $this->table(
            ['Period', 'Carts'],
            [
                ['Today', $today],
                ['This Week', $thisWeek],
                ['This Month', $thisMonth],
            ]
        );

        // Conversion rate
        $conversionRate = $total > 0 ? ($converted / $total) * 100 : 0;
        $abandonmentRate = $total > 0 ? ($abandoned / $total) * 100 : 0;

        $this->newLine();
        $this->info('Conversion Metrics');
        $this->table(
            ['Metric', 'Rate'],
            [
                ['Conversion Rate', round($conversionRate, 1) . '%'],
                ['Abandonment Rate', round($abandonmentRate, 1) . '%'],
            ]
        );

        return Command::SUCCESS;
    }

    protected function percentage(int $count, int $total): string
    {
        return $total > 0 ? round(($count / $total) * 100, 1) . '%' : '0%';
    }
}
