<?php

namespace Modules\Order\Http\Middleware;

use App\Services\MenuService;
use Closure;
use Illuminate\Http\Request;

/**
 * Registers the Order module's sidebar entries on dashboard requests.
 * Replaces the previous boot-time registration in OrderServiceProvider.
 */
class DashboardMiddlewareHandle
{
    protected static bool $registered = false;

    public function handle(Request $request, Closure $next)
    {
        if ($request->is('dashboard', 'dashboard/*')) {
            $this->registerMenuItems();
        }

        return $next($request);
    }

    protected function registerMenuItems(): void
    {
        if (static::$registered) {
            return;
        }

        MenuService::addMenuItem(
            'primary',
            'order',
            __('Orders'),
            route('order.orders.index'),
            'ShoppingBag',
            60,
            'orders.view_any',
            'order.*'
        );

        MenuService::addSubmenuItem('primary', 'order', __('All Orders'), route('order.orders.index'), 1, 'orders.view_any', 'order.orders.*', 'Package');
        MenuService::addSubmenuItem('primary', 'order', __('Carts'), route('order.carts.index'), 2, 'carts.view_any', 'order.carts.*', 'ShoppingCart');
        MenuService::addSubmenuItem('primary', 'order', __('Product Reviews'), route('order.product-reviews.index'), 3, 'product_reviews.view_any', 'order.product-reviews.*', 'Star');
        MenuService::addSubmenuItem('primary', 'order', __('Outlet Reviews'), route('order.outlet-reviews.index'), 4, 'outlet_reviews.view_any', 'order.outlet-reviews.*', 'Store');
        MenuService::addSubmenuItem('primary', 'order', __('Shipping Zones'), route('order.shipping-zones.index'), 5, 'shipping_zones.view_any', 'order.shipping-zones.*', 'MapPin');

        static::$registered = true;
    }
}
