<?php

namespace Modules\Order\Http\Requests\Dashboard\V1\ProductReview;

use Illuminate\Foundation\Http\FormRequest;

class ReplyProductReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reply' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reply.required' => 'Please enter a reply message.',
            'reply.max' => 'Reply cannot exceed 500 characters.',
        ];
    }
}
