<?php

namespace Modules\Order\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderService;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {}

    /**
     * List customer's orders with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $customer = $request->user();
        $perPage = $request->integer('per_page', 10);
        $status = $request->input('status');

        $query = Order::with(['items.product', 'outlet', 'shipping'])
            ->where('customer_id', $customer->id)
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $orders = $query->paginate($perPage);

        return response()->json([
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Get single order detail.
     */
    public function show(Request $request, Order $order): JsonResponse
    {
        $customer = $request->user();

        if ($order->customer_id !== $customer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $order->load(['items.product', 'outlet', 'shipping', 'transactions']);

        return response()->json([
            'data' => new OrderResource($order),
        ]);
    }

    /**
     * Place order from cart (checkout).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'shipping_address_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', 'string'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $customer = $request->user();

        // Find active cart
        $cart = \Modules\Order\Models\Cart::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->with(['items.product', 'outlet'])
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty.',
            ], 422);
        }

        $order = $this->orderService->createFromCart($cart, [
            'notes' => $request->notes,
            'payment_method' => $request->payment_method ?? 'cash',
        ]);

        $order->load(['items.product', 'outlet', 'shipping']);

        return response()->json([
            'message' => 'Order placed successfully.',
            'data' => new OrderResource($order),
        ], 201);
    }

    /**
     * Cancel an order.
     */
    public function cancel(Request $request, Order $order): JsonResponse
    {
        $customer = $request->user();

        if ($order->customer_id !== $customer->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if (!in_array($order->status->value, ['pending', 'confirmed'])) {
            return response()->json([
                'message' => 'This order cannot be cancelled.',
            ], 422);
        }

        $this->orderService->updateStatus($order, 'cancelled');

        return response()->json([
            'message' => 'Order cancelled successfully.',
            'data' => new OrderResource($order->fresh(['items.product', 'outlet'])),
        ]);
    }
}
