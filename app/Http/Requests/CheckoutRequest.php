<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->route('store')->is_listed;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => ['required', 'uuid'],
            'customer_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'regex:/^\+?[0-9][0-9\s-]{6,28}[0-9]$/', 'max:30'],
            'method' => ['required', Rule::in(['pickup', 'delivery'])],
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'qris'])],
            'bank_account_id' => ['nullable', Rule::requiredIf(fn () => $this->input('payment_method') === 'bank_transfer' && ! $this->filled('payment_bank')), 'integer'],
            'payment_bank' => ['nullable', Rule::in(['bca', 'bni', 'bri'])],
            'delivery_service' => ['sometimes', Rule::in(['standard', 'priority', 'hemat'])],
            'delivery_address' => ['nullable', 'required_if:method,delivery', 'string', 'max:500'],
            'latitude' => ['nullable', 'required_if:method,delivery', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_if:method,delivery', 'numeric', 'between:-180,180'],
            'quote_id' => ['nullable', 'required_if:method,delivery', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:50'],
            'items.*' => ['array:id,quantity,choices'],
            'items.*.id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:99'],
            'items.*.choices' => ['present', 'array', 'max:160'],
            'items.*.choices.*' => ['required', 'uuid'],
        ];
    }
}
