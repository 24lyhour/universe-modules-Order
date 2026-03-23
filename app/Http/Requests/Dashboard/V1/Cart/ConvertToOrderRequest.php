<?php

namespace Modules\Order\Http\Requests\Dashboard\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

class ConvertToOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'discount_amount' => 'discount',
            'tax_amount' => 'tax',
            'payment_method' => 'payment method',
        ];
    }
}
