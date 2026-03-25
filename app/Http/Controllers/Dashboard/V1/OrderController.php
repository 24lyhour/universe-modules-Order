<?php

namespace Modules\Order\Http\Controllers\Dashboard\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Enums\PaymentStatusEnum;
use Modules\Order\Http\Requests\Dashboard\V1\Order\StoreOrderRequest;
use Modules\Order\Http\Requests\Dashboard\V1\Order\UpdateOrderRequest;
use Modules\Order\Http\Requests\Dashboard\V1\Order\UpdateOrderStatusRequest;
use Modules\Order\Http\Requests\Dashboard\V1\Order\UpdatePaymentStatusRequest;
use Modules\Order\Http\Resources\OrderResource;
use Modules\Order\Models\Order;
use Modules\Order\Services\OrderService;
use Modules\Outlet\Models\Outlet;
use Momentum\Modal\Modal;

class OrderController extends Controller
{
    public function __construct(
        protected OrderService $orderService
    ) {
    }

    /**
     * Display a listing of orders.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'payment_status', 'outlet_id', 'customer_id', 'date_from', 'date_to']);
        $perPage = $request->integer('per_page', 10);

        $orders = $this->orderService->paginate($perPage, $filters);
        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('order::Dashboard/V1/Order/Index', [
            'orderItems' => OrderResource::collection($orders)->response()->getData(true),
            'filters' => $filters,
            'stats' => $this->orderService->getStats(),
            'outlets' => $outlets,
            'statuses' => $this->getStatusOptions(),
            'paymentStatuses' => $this->getPaymentStatusOptions(),
        ]);
    }

    /**
     * Show the form for creating a new order.
     */
    public function create(): Modal
    {
        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();
        $customers = Customer::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::modal('order::Dashboard/V1/Order/Create', [
            'outlets' => $outlets,
            'customers' => $customers,
            'statuses' => $this->getStatusOptions(),
            'paymentStatuses' => $this->getPaymentStatusOptions(),
        ])->baseRoute('order.orders.index');
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request): RedirectResponse
    {
        $this->orderService->create($request->validated());

        return redirect()->route('order.orders.index')
            ->with('success', 'Order created successfully.');
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order): Response
    {
        $order->load(['customer', 'outlet', 'items.product', 'shipping']);

        return Inertia::render('order::Dashboard/V1/Order/Show', [
            'order' => (new OrderResource($order))->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified order.
     */
    public function edit(Order $order): Modal
    {
        $order->load(['customer', 'outlet', 'items', 'shipping']);
        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();
        $customers = Customer::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::modal('order::Dashboard/V1/Order/Edit', [
            'order' => (new OrderResource($order))->resolve(),
            'outlets' => $outlets,
            'customers' => $customers,
            'statuses' => $this->getStatusOptions(),
            'paymentStatuses' => $this->getPaymentStatusOptions(),
        ])->baseRoute('order.orders.index');
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $this->orderService->update($order, $request->validated());

        return redirect()->route('order.orders.index')
            ->with('success', 'Order updated successfully.');
    }

    /**
     * Remove the specified order.
     */
    public function destroy(Order $order): RedirectResponse
    {
        $this->orderService->delete($order);

        return redirect()->route('order.orders.index')
            ->with('success', 'Order deleted successfully.');
    }

    /**
     * Show order status action modal.
     */
    public function statusModal(Order $order): Modal
    {
        $order->load(['customer', 'outlet', 'items.product', 'shipping']);

        return Inertia::modal('order::Dashboard/V1/Order/OrderAction', [
            'order' => (new OrderResource($order))->resolve(),
        ])->baseRoute('order.orders.index');
    }

    /**
     * Update order status.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->orderService->updateStatus($order, $request->validated()['status']);

        // If from modal, redirect to index; otherwise redirect back (for Show page)
        if ($request->boolean('from_modal')) {
            return redirect()->route('order.orders.index')
                ->with('success', 'Order status updated successfully.');
        }

        return redirect()->back()->with('success', 'Order status updated successfully.');
    }

    /**
     * Update payment status.
     */
    public function updatePaymentStatus(UpdatePaymentStatusRequest $request, Order $order): RedirectResponse
    {
        $this->orderService->updatePaymentStatus($order, $request->validated()['payment_status']);

        // If from modal, redirect to index; otherwise redirect back (for Show page)
        if ($request->boolean('from_modal')) {
            return redirect()->route('order.orders.index')
                ->with('success', 'Payment status updated successfully.');
        }

        return redirect()->back()->with('success', 'Payment status updated successfully.');
    }

    /**
     * Get status options.
     */
    protected function getStatusOptions(): array
    {
        return OrderStatusEnum::options();
    }

    /**
     * Get payment status options.
     */
    protected function getPaymentStatusOptions(): array
    {
        return PaymentStatusEnum::options();
    }
}
