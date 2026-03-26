<?php

namespace Modules\Order\Http\Controllers\Dashboard\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Order\Http\Requests\Dashboard\V1\ShippingZoneRequest\CheckDeliveryRequest;
use Modules\Order\Http\Requests\Dashboard\V1\ShippingZoneRequest\StoreShippingZoneRequest;
use Modules\Order\Http\Requests\Dashboard\V1\ShippingZoneRequest\UpdateShippingZoneRequest;
use Modules\Order\Http\Resources\ShippingZoneResource;
use Modules\Order\Enums\VehicleTypeEnum;
use Modules\Order\Enums\ZoneTypeEnum;
use Modules\Order\Models\ShippingZone;
use Modules\Order\Services\ShippingZoneService;
use Modules\Outlet\Models\Outlet;
use Momentum\Modal\Modal;

class ShippingZoneController extends Controller
{
    public function __construct(
        protected ShippingZoneService $shippingZoneService
    ) {
    }

    /**
     * Display a listing of shipping zones.
     */
    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'outlet_id', 'zone_type', 'vehicle_type', 'is_active']);
        $perPage = $request->integer('per_page', 10);

        $zones = $this->shippingZoneService->paginate($perPage, $filters);
        $outlets = Outlet::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('order::Dashboard/V1/ShippingZone/Index', [
            'shippingZones' => ShippingZoneResource::collection($zones)->response()->getData(true),
            'filters' => $filters,
            'stats' => $this->shippingZoneService->getStats(),
            'outlets' => $outlets,
            'zoneTypes' => ZoneTypeEnum::options(),
            'vehicleTypes' => VehicleTypeEnum::options(),
        ]);
    }

    /**
     * Show the form for creating a new shipping zone.
     */
    public function create(): Modal
    {
        $outlets = Outlet::select('id', 'name', 'latitude', 'longitude')->orderBy('name')->get();

        return Inertia::modal('order::Dashboard/V1/ShippingZone/Create', [
            'outlets' => $outlets,
            'zoneTypes' => ZoneTypeEnum::options(),
            'vehicleTypes' => VehicleTypeEnum::options(),
        ])->baseRoute('order.shipping-zones.index');
    }

    /**
     * Store a newly created shipping zone.
     */
    public function store(StoreShippingZoneRequest $request): RedirectResponse
    {
        $this->shippingZoneService->create($request->validated());

        return redirect()->route('order.shipping-zones.index')
            ->with('success', 'Shipping zone created successfully.');
    }

    /**
     * Display the specified shipping zone.
     */
    public function show(ShippingZone $shippingZone): Response
    {
        $shippingZone->load('outlet');

        return Inertia::render('order::Dashboard/V1/ShippingZone/Show', [
            'shippingZone' => (new ShippingZoneResource($shippingZone))->resolve(),
        ]);
    }

    /**
     * Show the form for editing the specified shipping zone.
     */
    public function edit(ShippingZone $shippingZone): Modal
    {
        $shippingZone->load('outlet');
        $outlets = Outlet::select('id', 'name', 'latitude', 'longitude')->orderBy('name')->get();

        return Inertia::modal('order::Dashboard/V1/ShippingZone/Edit', [
            'shippingZone' => (new ShippingZoneResource($shippingZone))->resolve(),
            'outlets' => $outlets,
            'zoneTypes' => ZoneTypeEnum::options(),
            'vehicleTypes' => VehicleTypeEnum::options(),
        ])->baseRoute('order.shipping-zones.index');
    }

    /**
     * Update the specified shipping zone.
     */
    public function update(UpdateShippingZoneRequest $request, ShippingZone $shippingZone): RedirectResponse
    {
        $this->shippingZoneService->update($shippingZone, $request->validated());

        return redirect()->route('order.shipping-zones.index')
            ->with('success', 'Shipping zone updated successfully.');
    }

    /**
     * Remove the specified shipping zone.
     */
    public function destroy(ShippingZone $shippingZone): RedirectResponse
    {
        $this->shippingZoneService->delete($shippingZone);

        return redirect()->route('order.shipping-zones.index')
            ->with('success', 'Shipping zone deleted successfully.');
    }

    /**
     * Toggle or set active status.
     */
    public function toggleActive(Request $request, ShippingZone $shippingZone): RedirectResponse
    {
        $status = $request->has('status') ? $request->boolean('status') : null;
        $this->shippingZoneService->toggleActive($shippingZone, $status);

        return redirect()->back()
            ->with('success', 'Shipping zone status updated.');
    }

    /**
     * Check delivery availability for a point.
     */
    public function checkDelivery(CheckDeliveryRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $info = $this->shippingZoneService->checkDelivery(
            $validated['latitude'],
            $validated['longitude'],
            $validated['outlet_id'] ?? null,
            $validated['order_amount'] ?? 0
        );

        if (!$info) {
            return response()->json([
                'available' => false,
                'message' => 'Delivery not available for this location.',
            ]);
        }

        return response()->json([
            'available' => $info['is_available'],
            'zone' => (new ShippingZoneResource($info['zone']))->resolve(),
            'delivery_fee' => $info['delivery_fee'],
            'estimated_minutes' => $info['estimated_minutes'],
            'min_order_amount' => $info['min_order_amount'],
            'free_delivery_threshold' => $info['free_delivery_threshold'],
            'vehicle_type' => $info['vehicle_type'],
            'distance_km' => $info['distance_km'],
        ]);
    }


}
