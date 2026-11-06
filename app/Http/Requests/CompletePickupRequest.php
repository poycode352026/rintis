<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class CompletePickupRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $code = Str::upper(trim($this->input('code')));
            $code = preg_replace('/^RINTIS-PICKUP:/', '', $code);
            $this->merge(['code' => preg_replace('/[\s-]+/', '', $code)]);
        }
    }

    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('store'));

        return true;
    }

    public function rules(): array
    {
        return ['keep_change' => ['sometimes', 'boolean'], 'code' => ['required', 'string', 'regex:/^[A-Z0-9]{16}$/'],
            'cash_received' => ['nullable', 'integer', 'min:0', 'max:999999999999999']];
    }
}
