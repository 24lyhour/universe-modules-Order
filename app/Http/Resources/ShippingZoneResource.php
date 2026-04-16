<?php

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    /**
     * Calculate area in km² for both circle and polygon zones.
     */
    protected function calculateAreaKm2(): ?float
    {
        if ($this->zone_type?->value === 'circle' && $this->radius) {
            // Circle: π * r² (radius in meters → convert to km)
            $radiusKm = $this->radius / 1000;
            return round(M_PI * $radiusKm * $radiusKm, 2);
        }

        if ($this->zone_type?->value === 'polygon' && $this->polygon_coordinates) {
            return $this->calculatePolygonAreaKm2();
        }

        return null;
    }

    /**
     * Calculate polygon area in km² using the Shoelace formula with lat/lng.
     */
    protected function calculatePolygonAreaKm2(): float
    {
        $coords = $this->polygon_coordinates;
        if (!$coords || count($coords) < 3) {
            return 0;
        }

        $n = count($coords);
        $area = 0;

        for ($i = 0; $i < $n; $i++) {
            $j = ($i + 1) % $n;
            $lat1 = deg2rad($coords[$i][0]);
            $lng1 = deg2rad($coords[$i][1]);
            $lat2 = deg2rad($coords[$j][0]);
            $lng2 = deg2rad($coords[$j][1]);

            $area += ($lng2 - $lng1) * (2 + sin($lat1) + sin($lat2));
        }

        $area = abs($area) * 6371 * 6371 / 2;

        return round($area, 2);
    }

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'outlet_id' => $this->outlet_id,
            'name' => $this->name,
            'description' => $this->description,
            'color' => $this->color,
            'zone_type' => $this->zone_type?->value,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'radius' => $this->radius,
            'polygon_coordinates' => $this->polygon_coordinates,
            'area_km2' => $this->calculateAreaKm2(),
            // Pricing
            'delivery_fee' => (float) $this->delivery_fee,
            'min_order_amount' => (float) $this->min_order_amount,
            'free_delivery_threshold' => $this->free_delivery_threshold ? (float) $this->free_delivery_threshold : null,
            'peak_hour_surcharge' => (float) $this->peak_hour_surcharge,
            'price_per_km' => $this->price_per_km ? (float) $this->price_per_km : null,
            // Capacity
            'max_orders_per_hour' => $this->max_orders_per_hour,
            'max_weight_kg' => $this->max_weight_kg ? (float) $this->max_weight_kg : null,
            'max_items' => $this->max_items,
            // Vehicle
            'vehicle_type' => $this->vehicle_type?->value,
            'driver_requirements' => $this->driver_requirements,
            'requires_special_handling' => $this->requires_special_handling,
            // Time
            'estimated_delivery_minutes' => $this->estimated_delivery_minutes,
            'operating_hours' => $this->operating_hours,
            'peak_hours' => $this->peak_hours,
            'blocked_dates' => $this->blocked_dates,
            // Status
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            // Relations
            'outlet' => $this->whenLoaded('outlet', fn () => [
                'id' => $this->outlet->id,
                'name' => $this->outlet->name,
                'latitude' => $this->outlet->latitude ? (float) $this->outlet->latitude : null,
                'longitude' => $this->outlet->longitude ? (float) $this->outlet->longitude : null,
            ]),
            // Timestamps
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
