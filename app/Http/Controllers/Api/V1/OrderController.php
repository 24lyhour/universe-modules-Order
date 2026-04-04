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

        // Find active cart (latest one)
        $cart = \Modules\Order\Models\Cart::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('is_active', true)
            ->with(['items.product', 'outlet'])
            ->latest()
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty.',
            ], 422);
        }

        // Validate shipping address
        $address = null;
        if ($request->shipping_address_id) {
            $address = \Modules\Customer\Models\CustomerShipping::where('id', $request->shipping_address_id)
                ->where('customer_id', $customer->id)
                ->first();

            if (!$address) {
                return response()->json([
                    'message' => 'Shipping address not found.',
                ], 422);
            }

            // Address must have coordinates
            if (!$address->latitude || !$address->longitude) {
                return response()->json([
                    'message' => 'Shipping address has no location pin. Please update your address with a map location.',
                ], 422);
            }

            // Must be within delivery zone
            if ($cart->outlet_id) {
                if (!\Modules\Order\Models\ShippingZone::isDeliveryAvailable(
                    (float) $address->latitude,
                    (float) $address->longitude,
                    $cart->outlet_id
                )) {
                    return response()->json([
                        'message' => 'Delivery is not available to this address. Please choose an address within our delivery zone.',
                    ], 422);
                }
            }
        } else {
            return response()->json([
                'message' => 'Shipping address is required.',
            ], 422);
        }

        $order = $this->orderService->createFromCart($cart, [
            'notes' => $request->notes,
            'payment_method' => $request->payment_method ?? 'cash',
        ]);

        // Create shipping record from customer's selected address
        if ($address) {
            $this->orderService->createShipping($order, [
                'recipient_name' => $address->recipient_name,
                'phone' => $address->phone_number,
                'street_1' => $address->street_address,
                'street_2' => $address->house_number,
                'city' => $address->district,
                'state' => $address->province,
                'postal_code' => null,
                'country' => 'Cambodia',
                'latitude' => $address->latitude,
                'longitude' => $address->longitude,
            ]);
        }

        $order->load(['items.product', 'outlet', 'shipping']);

        return response()->json([
            'message' => 'Order placed successfully.',
            'data' => new OrderResource($order),
        ], 201);
    }

    /**
     * Check if delivery is available for a given address.
     */
    public function checkDelivery(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'outlet_id' => ['nullable', 'integer'],
        ]);

        $lat = (float) $request->latitude;
        $lng = (float) $request->longitude;
        $outletId = $request->outlet_id;

        $info = $this->orderService->checkDeliveryAvailability($lat, $lng, $outletId);

        if (!$info) {
            return response()->json([
                'available' => false,
                'message' => 'Delivery is not available to this location.',
            ]);
        }

        return response()->json([
            'available' => $info['is_available'],
            'delivery_fee' => $info['delivery_fee'],
            'estimated_minutes' => $info['estimated_minutes'],
            'distance_km' => $info['distance_km'],
            'min_order_amount' => $info['min_order_amount'],
            'message' => $info['is_available']
                ? 'Delivery is available.'
                : 'This location is outside our delivery hours.',
        ]);
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
