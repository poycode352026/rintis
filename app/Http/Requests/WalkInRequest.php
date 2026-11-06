<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WalkInRequest extends CheckoutRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('store'));

        return true;
    }

    public function rules(): array
    {
        return [...parent::rules(), 'keep_change' => ['sometimes', 'boolean'], 'customer_name' => ['nullable', 'string', 'max:100'], 'phone' => ['nullable', 'string', 'max:30'], 'method' => ['required', Rule::in(['pickup'])], 'payment_method' => ['required', Rule::in(['cash', 'qris'])], 'cash_received' => ['nullable', 'required_if:payment_method,cash', 'integer', 'min:0', 'max:999999999']];
    }
}
