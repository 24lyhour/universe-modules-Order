<?php

namespace Modules\Order\Http\Controllers\Dashboard\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Customer\Models\Customer;
use Modules\Order\Enums\CartStatusEnum;
use Modules\Order\Http\Requests\Dashboard\V1\Cart\ConvertToOrderRequest;
use Modules\Order\Http\Requests\Dashboard\V1\Cart\StoreCartRequest;
use Modules\Order\Http\Requests\Dashboard\V1\Cart\ToggleCartStatusRequest;
use Modules\Order\Http\Requests\Dashboard\V1\Cart\UpdateCartRequest;
use Modules\Order\Http\Resources\CartResource;
use Modules\Order\Models\Cart;
use Modules\Order\Services\CartService;
use Modules\Order\Services\OrderService;
use Modules\Outlet\Models\Outlet;
use Momentum\Modal\Modal;

class CartController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected OrderService $orderService
    ) {
    }

    /**
     * Display a listing of carts.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'status', 'is_active', 'outlet_id', 'customer_id']);
        $perPage = $request->integer('per_page', 10);

        $carts = $this->cartService->paginate($perPage, $filters);
        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('order::Dashboard/V1/Cart/Index', [
            'cartItems' => CartResource::collection($carts)->response()->getData(true),
            'filters' => $filters,
            'stats' => $this->cartService->getStats(),
            'outlets' => $outlets,
            'statuses' => $this->getStatusOptions(),
        ]);
    }

    /**
     * Show the form for creating a new cart.
     */
    public function create(): Modal
    {
        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();
        $customers = Customer::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::modal('order::Dashboard/V1/Cart/Create', [
            'outlets' => $outlets,
            'customers' => $customers,
            'statuses' => $this->getStatusOptions(),
        ])->baseRoute('order.carts.index');
    }

    /**
     * Store a newly created cart.
     */
    public function store(StoreCartRequest $request): RedirectResponse
    {
        $this->cartService->create($request->validated());

        return redirect()->route('order.carts.index')
            ->with('success', 'Cart created successfully.');
    }

    /**
     * Display the specified cart.
     */
    public function show(Cart $cart): Response
    {
        $cart->load(['customer', 'outlet', 'items.product']);

        return Inertia::render('order::Dashboard/V1/Cart/Show', [
            'cart' => (new CartResource($cart))->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified cart.
     */
    public function edit(Cart $cart): Modal
    {
        $cart->load(['customer', 'outlet', 'items']);
        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();
        $customers = Customer::select('id', 'name', 'email')->orderBy('name')->get();

        return Inertia::modal('order::Dashboard/V1/Cart/Edit', [
            'cart' => (new CartResource($cart))->resolve(),
            'outlets' => $outlets,
            'customers' => $customers,
            'statuses' => $this->getStatusOptions(),
        ])->baseRoute('order.carts.index');
    }

    /**
     * Update the specified cart.
     */
    public function update(UpdateCartRequest $request, Cart $cart): RedirectResponse
    {
        $this->cartService->update($cart, $request->validated());

        return redirect()->route('order.carts.index')
            ->with('success', 'Cart updated successfully.');
    }

    /**
     * Remove the specified cart.
     */
    public function destroy(Cart $cart): RedirectResponse
    {
        $this->cartService->delete($cart);

        return redirect()->route('order.carts.index')
            ->with('success', 'Cart deleted successfully.');
    }

    /**
     * Convert cart to order.
     */
    public function convertToOrder(ConvertToOrderRequest $request, Cart $cart): RedirectResponse
    {
        $order = $this->orderService->createFromCart($cart, $request->validated());

        return redirect()->route('order.orders.show', $order)
            ->with('success', 'Cart converted to order successfully.');
    }

    /**
     * Toggle cart active status.
     */
    public function toggleStatus(ToggleCartStatusRequest $request, Cart $cart): RedirectResponse
    {
        $this->cartService->update($cart, ['is_active' => $request->validated()['is_active']]);

        return redirect()->back()->with('success', 'Cart status updated successfully.');
    }

    /**
     * Clear all items from cart.
     */
    public function clearItems(Cart $cart): RedirectResponse
    {
        $this->cartService->clearItems($cart);

        return redirect()->back()->with('success', 'Cart items cleared successfully.');
    }

    /**
     * Get status options.
     */
    protected function getStatusOptions(): array
    {
        return CartStatusEnum::options();
    }
}
