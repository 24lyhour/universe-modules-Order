<?php

namespace Modules\Order\Http\Requests\Dashboard\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Order\Enums\CartStatusEnum;

class StoreCartRequest extends FormRequest
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
            'customer_id' => ['nullable', 'exists:customers,id'],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'status' => ['required', Rule::in(CartStatusEnum::values())],
            'notes' => ['nullable', 'string'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'customer_id' => 'customer',
            'outlet_id' => 'outlet',
            'expires_at' => 'expiry date',
            'is_active' => 'active status',
        ];
    }
}
