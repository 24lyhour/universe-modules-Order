<?php

namespace Modules\Order\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Modules\Order\Models\ShippingZone;

class ShippingZoneService
{
    /**
     * Get paginated shipping zones with filters.
     */
    public function paginate(int $perPage = 10, array $filters = []): LengthAwarePaginator
    {
        $query = ShippingZone::query()->with('outlet');

        // Search filter
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Outlet filter
        if (!empty($filters['outlet_id'])) {
            $query->where('outlet_id', $filters['outlet_id']);
        }

        // Zone type filter
        if (!empty($filters['zone_type'])) {
            $query->where('zone_type', $filters['zone_type']);
        }

        // Vehicle type filter
        if (!empty($filters['vehicle_type'])) {
            $query->where('vehicle_type', $filters['vehicle_type']);
        }

        // Active status filter
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active'] === 'true' || $filters['is_active'] === true);
        }

        return $query
            ->orderBy('priority')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Create a new shipping zone.
     */
    public function create(array $data): ShippingZone
    {
        $data['created_by'] = auth()->id();

        $zone = ShippingZone::create($data);
        $this->clearStatsCache();

        return $zone;
    }

    /**
     * Update an existing shipping zone.
     */
    public function update(ShippingZone $shippingZone, array $data): ShippingZone
    {
        $data['updated_by'] = auth()->id();

        $shippingZone->update($data);
        $this->clearStatsCache();

        return $shippingZone->fresh();
    }

    /**
     * Delete a shipping zone.
     */
    public function delete(ShippingZone $shippingZone): bool
    {
        $deleted = $shippingZone->delete();
        $this->clearStatsCache();

        return $deleted;
    }

    /**
     * Toggle or set active status of a shipping zone.
     */
    public function toggleActive(ShippingZone $shippingZone, ?bool $status = null): ShippingZone
    {
        $shippingZone->update([
            'is_active' => $status ?? !$shippingZone->is_active,
            'updated_by' => auth()->id(),
        ]);
        $this->clearStatsCache();

        return $shippingZone->fresh();
    }

    /**
     * Check delivery availability for a location.
     */
    public function checkDelivery(float $latitude, float $longitude, ?int $outletId = null, float $orderAmount = 0): ?array
    {
        return ShippingZone::getDeliveryInfo($latitude, $longitude, $outletId, $orderAmount);
    }

    /**
     * Get shipping zone statistics.
     */
    public function getStats(): array
    {
        return Cache::remember('shipping_zone_stats', 300, function () {
            return [
                'total'     => ShippingZone::count(),
                'active'    => ShippingZone::where('is_active', true)->count(),
                'inactive'  => ShippingZone::where('is_active', false)->count(),
                'by_type'   => [
                    'circle'     => ShippingZone::where('zone_type', 'circle')->count(),
                    'polygon'    => ShippingZone::where('zone_type', 'polygon')->count(),
                ],
                'by_vehicle' => [
                    'bike'       => ShippingZone::where('vehicle_type', 'bike')->count(),
                    'motorcycle' => ShippingZone::where('vehicle_type', 'motorcycle')->count(),
                    'car' => ShippingZone::where('vehicle_type', 'car')->count(),
                    'van' => ShippingZone::where('vehicle_type', 'van')->count(),
                    'truck' => ShippingZone::where('vehicle_type', 'truck')->count(),
                ],
            ];
        });
    }

    /**
     * Clear statistics cache.
     */
    public function clearStatsCache(): void
    {
        Cache::forget('shipping_zone_stats');
    }
}
