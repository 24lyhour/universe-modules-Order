<?php

namespace Modules\Order\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingZoneResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
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
