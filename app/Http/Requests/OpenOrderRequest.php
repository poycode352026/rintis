<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class OpenOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('store'));

        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'intent' => ['required', Rule::in(['draft', 'publish'])],
            'opens_at' => ['required', 'date_format:Y-m-d\TH:i'],
            'closes_at' => ['required', 'date_format:Y-m-d\TH:i', 'after:opens_at'],
            'pickup_enabled' => ['required', 'boolean'],
            'delivery_enabled' => ['required', 'boolean'],
            'delivery_starts_at' => [Rule::requiredIf(fn () => $this->input('intent') === 'publish' && $this->boolean('delivery_enabled')), 'nullable', 'date_format:Y-m-d\\TH:i', 'after_or_equal:closes_at'],
            'delivery_ends_at' => [Rule::requiredIf(fn () => $this->input('intent') === 'publish' && $this->boolean('delivery_enabled')), 'nullable', 'date_format:Y-m-d\\TH:i', 'after:delivery_starts_at'],
            'delivery_fee_mode' => ['sometimes', Rule::in(['fixed', 'road_distance'])],
            'delivery_fixed_fee' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'delivery_rate_per_km' => ['nullable', 'integer', 'min:0', 'max:999999999'],
            'priority_enabled' => ['sometimes', 'boolean'],
            'priority_fee' => [Rule::requiredIf(fn () => $this->boolean('priority_enabled')), 'nullable', 'integer', 'min:0', 'max:9999999'],
            'priority_minutes' => [Rule::requiredIf(fn () => $this->boolean('priority_enabled')), 'nullable', 'integer', 'min:1', 'max:1440'],
            'hemat_enabled' => ['sometimes', 'boolean'],
            'hemat_fee' => [Rule::requiredIf(fn () => $this->boolean('hemat_enabled')), 'nullable', 'integer', 'min:0', 'max:9999999'],
            'hemat_minutes' => [Rule::requiredIf(fn () => $this->boolean('hemat_enabled')), 'nullable', 'integer', 'min:1', 'max:1440'],
            'items' => ['present', 'array', 'max:100'],
            'items.*' => ['array:catalog_product_id,price,quota'],
            'items.*.catalog_product_id' => ['required', 'integer', 'distinct', Rule::exists('rintis_catalog_products', 'id')->where('store_id', $this->route('store')->id)->whereNull('deleted_at')],
            'items.*.price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'items.*.quota' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
