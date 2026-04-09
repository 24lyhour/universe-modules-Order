<?php

namespace Modules\Order\Http\Requests\Dashboard\V1\ShippingZoneRequest;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Outlet\Models\Outlet;

class StoreShippingZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Zone Info
            'outlet_id' => [
                'required',
                'exists:outlets,id',
                function ($attribute, $value, $fail) {
                    $outlet = Outlet::find($value);
                    if ($outlet && ($outlet->latitude === null || $outlet->longitude === null)) {
                        $fail('The selected outlet does not have a location set. Please configure the outlet\'s address first.');
                    }
                },
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'color' => ['required', 'string', 'max:7', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'zone_type' => ['required', Rule::in(['circle', 'polygon'])],
            'priority' => ['nullable', 'integer', 'min:0'],

            // Geofence
            'latitude' => ['nullable', 'required_if:zone_type,circle', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_if:zone_type,circle', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'required_if:zone_type,circle', 'integer', 'min:50', 'max:100000'],
            'polygon_coordinates' => ['nullable', 'required_if:zone_type,polygon', 'array', 'min:3'],

            // Pricing
            'delivery_fee' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'free_delivery_threshold' => ['nullable', 'numeric', 'min:0'],
            'peak_hour_surcharge' => ['nullable', 'numeric', 'min:0'],
            'price_per_km' => ['nullable', 'numeric', 'min:0'],

            // Capacity
            'max_orders_per_hour' => ['nullable', 'integer', 'min:1'],
            'max_weight_kg' => ['nullable', 'numeric', 'min:0'],
            'max_items' => ['nullable', 'integer', 'min:1'],

            // Vehicle
            'vehicle_type' => ['required', Rule::in(['bike', 'motorcycle', 'car', 'van', 'truck'])],
            'driver_requirements' => ['nullable', 'string', 'max:1000'],
            'requires_special_handling' => ['boolean'],

            // Time
            'estimated_delivery_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'operating_hours' => ['nullable', 'array'],
            'peak_hours' => ['nullable', 'array'],
            'blocked_dates' => ['nullable', 'array'],

            // Status
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Please select an outlet.',
            'name.required'      => 'Zone name is required.',
            'color.regex'        => 'Color must be a valid hex color (e.g., #3B82F6).',
            'latitude.required'  => 'Please set the zone location on the map.',
            'radius.required_if' => 'Radius is required for circle zones.',
            'polygon_coordinates.required_if' => 'Please draw the polygon zone on the map.',
            'vehicle_type.required' => 'Please select a vehicle type.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active', true),
            'requires_special_handling' => $this->boolean('requires_special_handling', false),
            'delivery_fee' => $this->input('delivery_fee') ?? 0,
            'min_order_amount' => $this->input('min_order_amount') ?? 0,
            'peak_hour_surcharge' => $this->input('peak_hour_surcharge') ?? 0,
            'priority' => $this->input('priority') ?? 0,
        ]);
    }
}
