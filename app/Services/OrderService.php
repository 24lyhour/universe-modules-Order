<?php

namespace Modules\Order\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Enums\PaymentStatusEnum;
use Modules\Order\Models\Cart;
use Modules\Order\Models\Order;
use Modules\Order\Models\OrderItem;

class OrderService
{
    /**
     * Get paginated orders with filters.
     */
    public function paginate(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = Order::query()
            ->with(['customer', 'outlet'])
            ->withCount('items');

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Payment status filter
        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        // Outlet filter
        if (!empty($filters['outlet_id'])) {
            $query->where('outlet_id', $filters['outlet_id']);
        }

        // Customer filter
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new order from cart.
     */
    public function createFromCart(Cart $cart, array $data = []): Order
    {
        return DB::transaction(function () use ($cart, $data) {
            $cart->load('items.product');

            // Create order
            $order = Order::create([
                'customer_id' => $cart->customer_id,
                'outlet_id' => $cart->outlet_id,
                'cart_id' => $cart->id,
                'subtotal' => $cart->total_amount,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'total_amount' => $cart->total_amount - ($data['discount_amount'] ?? 0) + ($data['tax_amount'] ?? 0),
                'status' => OrderStatusEnum::Pending,
                'payment_status' => PaymentStatusEnum::Pending,
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create order items from cart items
            foreach ($cart->items as $cartItem) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'product_sku' => $cartItem->product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $cartItem->unit_price,
                    'discount_amount' => 0,
                    'total_amount' => $cartItem->total_amount,
                    'notes' => $cartItem->notes,
                ]);
            }

            // Mark cart as converted
            $cart->markAsConverted();

            $this->clearStatsCache();

            return $order;
        });
    }

    /**
     * Create a new order manually.
     */
    public function create(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'customer_id' => $data['customer_id'] ?? null,
                'outlet_id' => $data['outlet_id'] ?? null,
                'subtotal' => $data['subtotal'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'total_amount' => $data['total_amount'] ?? 0,
                'status' => $data['status'] ?? OrderStatusEnum::Pending,
                'payment_status' => $data['payment_status'] ?? PaymentStatusEnum::Pending,
                'payment_method' => $data['payment_method'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create order items if provided
            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'product_sku' => $item['product_sku'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'total_amount' => $item['total_amount'],
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            $this->clearStatsCache();

            return $order;
        });
    }

    /**
     * Update an existing order.
     */
    public function update(Order $order, array $data): Order
    {
        $order->update($data);
        $this->clearStatsCache();

        return $order->fresh();
    }

    /**
     * Update order status.
     */
    public function updateStatus(Order $order, string $status): Order
    {
        $updateData = ['status' => $status];

        // Set timestamps based on status
        match ($status) {
            OrderStatusEnum::Delivering->value, 'delivering' => $updateData['shipped_at'] = now(),
            OrderStatusEnum::Delivered->value, 'delivered' => $updateData['delivered_at'] = now(),
            OrderStatusEnum::Cancelled->value, 'cancelled' => $updateData['cancelled_at'] = now(),
            default => null,
        };

        $order->update($updateData);
        $this->clearStatsCache();

        return $order->fresh();
    }

    /**
     * Update payment status.
     */
    public function updatePaymentStatus(Order $order, string $paymentStatus): Order
    {
        $order->update(['payment_status' => $paymentStatus]);
        $this->clearStatsCache();

        return $order->fresh();
    }

    /**
     * Delete an order.
     */
    public function delete(Order $order): bool
    {
        $deleted = $order->delete();
        $this->clearStatsCache();

        return $deleted;
    }

    /**
     * Get order statistics.
     */
    public function getStats(): array
    {
        return Cache::remember('order_stats', 300, function () {
            return [
                'total' => Order::count(),
                'pending' => Order::where('status', OrderStatusEnum::Pending)->count(),
                'confirmed' => Order::where('status', OrderStatusEnum::Confirmed)->count(),
                'preparing' => Order::where('status', OrderStatusEnum::Preparing)->count(),
                'ready' => Order::where('status', OrderStatusEnum::Ready)->count(),
                'delivering' => Order::where('status', OrderStatusEnum::Delivering)->count(),
                'delivered' => Order::where('status', OrderStatusEnum::Delivered)->count(),
                'completed' => Order::where('status', OrderStatusEnum::Completed)->count(),
                'cancelled' => Order::where('status', OrderStatusEnum::Cancelled)->count(),
                'refunded' => Order::where('status', OrderStatusEnum::Refunded)->count(),
                'total_revenue' => Order::where('payment_status', PaymentStatusEnum::Paid)->sum('total_amount'),
                'payment_pending' => Order::where('payment_status', PaymentStatusEnum::Pending)->count(),
                'payment_paid' => Order::where('payment_status', PaymentStatusEnum::Paid)->count(),
            ];
        });
    }

    /**
     * Clear statistics cache.
     */
    public function clearStatsCache(): void
    {
        Cache::forget('order_stats');
    }
}
