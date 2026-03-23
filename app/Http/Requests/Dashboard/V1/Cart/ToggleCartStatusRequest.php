<?php

namespace Modules\Order\Http\Requests\Dashboard\V1\Cart;

use Illuminate\Foundation\Http\FormRequest;

class ToggleCartStatusRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'is_active' => 'active status',
        ];
    }
}
