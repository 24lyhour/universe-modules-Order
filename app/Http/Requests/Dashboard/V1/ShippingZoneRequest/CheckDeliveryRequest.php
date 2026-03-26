<?php

namespace Modules\Order\Http\Requests\Dashboard\V1\ShippingZoneRequest;

use Illuminate\Foundation\Http\FormRequest;

class CheckDeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'latitude'  => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'order_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'latitude.required' => 'Latitude is required.',
            'latitude.between'  => 'Latitude must be between -90 and 90.',
            'longitude.required' => 'Longitude is required.',
            'longitude.between'  => 'Longitude must be between -180 and 180.',
            'outlet_id.exists'   => 'The selected outlet is invalid.',
            'order_amount.numeric' => 'Order amount must be a number.',
            'order_amount.min'     => 'Order amount cannot be negative.',
        ];
    }
}
