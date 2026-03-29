<?php

namespace Modules\Order\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Order\Models\OutletReview;

class UpdateOutletReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'overall_rating' => ['sometimes', 'required', 'integer', 'min:1', 'max:5'],
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
}
