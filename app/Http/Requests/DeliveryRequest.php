<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class DeliveryRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('store'));

        return true;
    }

    public function rules(): array
    {
        return ['orders' => ['required', 'array', 'min:1', 'max:10'], 'orders.*' => ['required', 'integer', 'distinct'],
            'stage' => ['nullable', Rule::in(['preparing', 'ready'])]];
    }
}
