<?php

namespace Modules\Order\Http\Requests\Dashboard\V1\OutletReview;

use Illuminate\Foundation\Http\FormRequest;

class ReplyOutletReviewRequest extends FormRequest
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
