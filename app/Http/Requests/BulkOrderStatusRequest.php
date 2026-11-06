<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class BulkOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('store'));

        return true;
    }

    public function rules(): array
    {
        return ['orders' => ['required', 'array', 'min:1', 'max:1000'], 'orders.*' => ['required', 'integer', 'distinct'],
            'status' => ['required', Rule::in(['preparing', 'ready', 'out_for_delivery'])]];
    }
}
