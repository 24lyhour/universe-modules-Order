<?php

namespace Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Order\Http\Resources\CartResource;
use Modules\Order\Http\Resources\CartItemResource;
use Modules\Order\Models\Cart;
use Modules\Order\Models\CartItem;
use Modules\Order\Services\CartService;
use Modules\Product\Models\Product;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService
    ) {}

    /**
     * Get current customer's cart with items.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();

        $cart = $this->cartService->getOrCreateForCustomer($customer->id);
        $cart->load(['items.product', 'outlet']);

        return response()->json([
            'data' => $cart->items->map(function (CartItem $item) {
                $product = $item->product;
                return [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $product?->name ?? '',
                    'product_image' => $product?->images[0] ?? null,
                    'price' => (float) $item->unit_price,
                    'original_price' => $product?->price != $product?->sale_price
                        ? (float) $product?->price
                        : null,
                    'quantity' => $item->quantity,
                    'variant' => null,
                    'note' => $item->notes,
                    'outlet_id' => $item->cart?->outlet_id,
                    'outlet_name' => $item->cart?->outlet?->name,
                ];
            }),
            'cart' => [
                'id' => $cart->id,
                'uuid' => $cart->uuid,
                'total_amount' => $cart->total_amount,
                'total_quantity' => $cart->total_quantity,
                'items_count' => $cart->items->count(),
            ],
        ]);
    }

    /**
     * Add item to cart.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $customer = $request->user();
        $product = Product::findOrFail($request->product_id);

        // Get or create cart, optionally tied to product's outlet
        $cart = $this->cartService->getOrCreateForCustomer(
            $customer->id,
            $product->outlet_id ?? null
        );

        $item = $this->cartService->addItem(
            $cart,
            $product,
            $request->integer('quantity'),
            $request->notes
        );

        $item->load('product');

        return response()->json([
            'message' => 'Item added to cart.',
            'data' => new CartItemResource($item),
        ], 201);
    }

    /**
     * Update cart item quantity.
     */
    public function update(Request $request, int $itemId): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:0'],
        ]);

        $customer = $request->user();

        $item = CartItem::whereHas('cart', function ($q) use ($customer) {
            $q->where('customer_id', $customer->id);
        })->findOrFail($itemId);

        $quantity = $request->integer('quantity');

        if ($quantity <= 0) {
            $this->cartService->removeItem($item);
            return response()->json(['message' => 'Item removed from cart.']);
        }

        $this->cartService->updateItemQuantity($item, $quantity);

        return response()->json([
            'message' => 'Cart updated.',
            'data' => new CartItemResource($item->fresh('product')),
        ]);
    }

    /**
     * Remove item from cart.
     */
    public function destroy(Request $request, int $itemId): JsonResponse
    {
        $customer = $request->user();

        $item = CartItem::whereHas('cart', function ($q) use ($customer) {
            $q->where('customer_id', $customer->id);
        })->findOrFail($itemId);

        $this->cartService->removeItem($item);

        return response()->json(['message' => 'Item removed from cart.']);
    }

    /**
     * Clear entire cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $customer = $request->user();

        $cart = Cart::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->first();

        if ($cart) {
            $this->cartService->clearItems($cart);
        }

        return response()->json(['message' => 'Cart cleared.']);
    }
}
