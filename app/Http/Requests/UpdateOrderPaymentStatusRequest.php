<?php

namespace Modules\Order\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Order\Enums\PaymentStatusEnum;

class UpdateOrderPaymentStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('orders.update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'payment_status' => [
                'required',
                Rule::in(PaymentStatusEnum::values()),
            ],
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'payment_status.required' => 'Please select a payment status.',
            'payment_status.in' => 'The selected payment status is invalid.',
        ];
    }
}
