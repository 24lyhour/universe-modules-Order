<?php

namespace Modules\Order\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'exists:order_orders,id'],
            'order_item_id' => ['required', 'exists:order_order_items,id', 'unique:order_product_reviews,order_item_id'],
            'product_id' => ['required', 'exists:products,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'images' => ['nullable', 'array', 'max:5'],
            'images.*' => ['string', 'url'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_item_id.unique' => 'This item has already been reviewed.',
            'rating.min' => 'Rating must be at least 1 star.',
            'rating.max' => 'Rating cannot exceed 5 stars.',
            'images.max' => 'You can upload up to 5 images.',
        ];
    }
}
