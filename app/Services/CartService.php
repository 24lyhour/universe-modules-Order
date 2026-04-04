<?php

namespace Modules\Order\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Order\Enums\CartStatusEnum;
use Modules\Order\Models\Cart;
use Modules\Order\Models\CartItem;
use Modules\Product\Models\Product;

class CartService
{
    /**
     * Get paginated carts with filters.
     */
    public function paginate(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = Cart::query()
            ->with(['customer', 'outlet'])
            ->withCount('items');

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('uuid', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        // Status filter
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Active filter
        if (isset($filters['is_active'])) {
            $query->where('is_active', filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN));
        }

        // Outlet filter
        if (!empty($filters['outlet_id'])) {
            $query->where('outlet_id', $filters['outlet_id']);
        }

        // Customer filter
        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', $filters['customer_id']);
        }

        return $query->latest()->paginate($perPage);
    }

    /**
     * Create a new cart.
     */
    public function create(array $data): Cart
    {
        $cart = Cart::create([
            'customer_id' => $data['customer_id'] ?? null,
            'outlet_id' => $data['outlet_id'] ?? null,
            'status' => $data['status'] ?? CartStatusEnum::Active,
            'notes' => $data['notes'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->clearStatsCache();

        return $cart;
    }

    /**
     * Update an existing cart.
     */
    public function update(Cart $cart, array $data): Cart
    {
        $cart->update($data);
        $this->clearStatsCache();

        return $cart->fresh();
    }

    /**
     * Delete a cart.
     */
    public function delete(Cart $cart): bool
    {
        $deleted = $cart->delete();
        $this->clearStatsCache();

        return $deleted;
    }

    /**
     * Add item to cart.
     */
    public function addItem(Cart $cart, Product $product, int $quantity = 1, ?string $notes = null): CartItem
    {
        return DB::transaction(function () use ($cart, $product, $quantity, $notes) {
            // Check if item already exists
            $existingItem = $cart->items()->where('product_id', $product->id)->first();

            if ($existingItem) {
                $existingItem->update([
                    'quantity' => $existingItem->quantity + $quantity,
                ]);
                return $existingItem->fresh();
            }

            // Create new item
            return CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $product->effective_price,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Update cart item quantity.
     */
    public function updateItemQuantity(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $item->delete();
            return $item;
        }

        $item->update(['quantity' => $quantity]);
        return $item->fresh();
    }

    /**
     * Remove item from cart.
     */
    public function removeItem(CartItem $item): bool
    {
        return $item->delete();
    }

    /**
     * Clear all items from cart.
     */
    public function clearItems(Cart $cart): bool
    {
        return $cart->items()->delete() > 0;
    }

    /**
     * Get or create active cart for customer.
     */
    public function getOrCreateForCustomer(int $customerId, ?int $outletId = null): Cart
    {
        $query = Cart::where('customer_id', $customerId)
            ->where('status', CartStatusEnum::Active)
            ->where('is_active', true);

        // If outlet specified, find cart for that outlet first
        if ($outletId) {
            $cart = (clone $query)->where('outlet_id', $outletId)->latest()->first();

            if ($cart) {
                return $cart;
            }
        }

        // Find any active cart
        $cart = $query->latest()->first();

        if (!$cart) {
            $cart = $this->create([
                'customer_id' => $customerId,
                'outlet_id' => $outletId,
            ]);
        } elseif ($outletId && $cart->outlet_id !== $outletId) {
            // Different outlet → create new cart for this outlet
            $cart = $this->create([
                'customer_id' => $customerId,
                'outlet_id' => $outletId,
            ]);
        } elseif ($outletId && !$cart->outlet_id) {
            $cart->update(['outlet_id' => $outletId]);
        }

        return $cart;
    }

    /**
     * Mark expired carts.
     */
    public function markExpiredCarts(): int
    {
        $count = Cart::where('status', CartStatusEnum::Active)
            ->where('expires_at', '<', now())
            ->update(['status' => CartStatusEnum::Expired]);

        if ($count > 0) {
            $this->clearStatsCache();
        }

        return $count;
    }

    /**
     * Get cart statistics.
     */
    public function getStats(): array
    {
        return Cache::remember('cart_stats', 300, function () {
            return [
                'total' => Cart::count(),
                'active' => Cart::where('status', CartStatusEnum::Active)->where('is_active', true)->count(),
                'abandoned' => Cart::where('status', CartStatusEnum::Abandoned)->count(),
                'converted' => Cart::where('status', CartStatusEnum::Converted)->count(),
                'expired' => Cart::where('status', CartStatusEnum::Expired)->count(),
            ];
        });
    }

    /**
     * Clear statistics cache.
     */
    public function clearStatsCache(): void
    {
        Cache::forget('cart_stats');
    }
}
