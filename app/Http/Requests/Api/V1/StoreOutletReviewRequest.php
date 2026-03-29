<?php

namespace Modules\Order\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Order\Models\OutletReview;

class StoreOutletReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:order_orders,id', 'unique:order_outlet_reviews,order_id'],
            'outlet_id' => ['required', 'exists:outlets,id'],
            'overall_rating' => ['required', 'integer', 'min:1', 'max:5'],
            'food_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'service_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'delivery_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'packaging_rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['string', 'url'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'in:' . implode(',', array_keys(OutletReview::TAGS))],
        ];
    }

    public function messages(): array
    {
        return [
            'order_id.unique' => 'This order has already been reviewed.',
            'overall_rating.required' => 'Please provide an overall rating.',
            'overall_rating.min' => 'Rating must be at least 1 star.',
            'overall_rating.max' => 'Rating cannot exceed 5 stars.',
        ];
    }
}
