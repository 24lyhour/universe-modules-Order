<?php

namespace Modules\Order\Http\Requests\Dashboard\V1\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Order\Enums\OrderStatusEnum;
use Modules\Order\Enums\PaymentStatusEnum;

class StoreOrderRequest extends FormRequest
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
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(OrderStatusEnum::values())],
            'payment_status' => ['required', Rule::in(PaymentStatusEnum::values())],
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
            'customer_id' => 'customer',
            'outlet_id' => 'outlet',
            'subtotal' => 'subtotal',
            'discount_amount' => 'discount',
            'tax_amount' => 'tax',
            'total_amount' => 'total',
            'payment_status' => 'payment status',
            'payment_method' => 'payment method',
        ];
    }
}
