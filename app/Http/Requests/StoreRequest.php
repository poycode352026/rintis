<?php

namespace App\Http\Requests;

use App\Models\Store;
use App\Services\RegionDirectory;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Throwable;

class StoreRequest extends FormRequest
{
    private array $regionNames = [];

    protected function prepareForValidation(): void
    {
        $store = $this->route('store');
        if ($store instanceof Store) {
            foreach (['category_id', 'province', 'city', 'district', 'address_line'] as $field) {
                if (! $this->exists($field)) {
                    $this->merge([$field => $store->{$field}]);
                }
            }
        }
        $this->merge(['is_listed' => true, 'show_full_address' => false]);
        if (is_string($this->input('name'))) {
            $this->merge(['name' => Str::squish($this->input('name'))]);
        }
        if (is_string($this->input('slug'))) {
            $this->merge(['slug' => Str::slug($this->input('slug'))]);
        }
        $accounts = $this->input('bank_accounts');
        if (is_array($accounts)) {
            foreach ($accounts as &$account) {
                if (is_array($account) && isset($account['account_number']) && is_string($account['account_number'])) {
                    $account['account_number'] = preg_replace('/[\s-]+/', '', $account['account_number']);
                }
            }
            $this->merge(['bank_accounts' => $accounts]);
        }
    }

    public function storeAttributes(): array
    {
        $attributes = array_replace($this->safe()->except(['bank_accounts', 'deleted_bank_account_ids', 'priority_enabled', 'priority_fee', 'priority_minutes', 'hemat_enabled', 'hemat_fee', 'hemat_minutes']), $this->regionNames);
        if ($this->has('priority_enabled') || $this->has('hemat_enabled')) {
            $attributes['delivery_options'] = ['priority' => ['enabled' => $this->boolean('priority_enabled'), 'fee' => (int) $this->input('priority_fee', 0), 'minutes' => (int) $this->input('priority_minutes', 30)], 'hemat' => ['enabled' => $this->boolean('hemat_enabled'), 'fee' => (int) $this->input('hemat_fee', 0), 'minutes' => (int) $this->input('hemat_minutes', 60)]];
        }

        return $attributes;
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $store = $this->route('store');
            if ($this->boolean('delivery_enabled')) {
                if ($this->integer('delivery_min_distance_m') > $this->integer('delivery_max_distance_m')) {
                    $validator->errors()->add('delivery_min_distance_m', 'Minimum distance must not exceed maximum distance.');

                    return;
                }
                $latitude = $this->input('latitude', $store?->latitude);
                $longitude = $this->input('longitude', $store?->longitude);
                if ($latitude === null || $latitude === '' || $longitude === null || $longitude === '') {
                    $validator->errors()->add('delivery_enabled', 'Place your store pin before enabling delivery.');

                    return;
                }
            }
            if (! $this->filled('province_code')) {
                return;
            }
            if ($store instanceof Store && $this->input('province_code') === $store->province_code
                && $this->input('city_code') === $store->city_code && $this->input('district_code') === $store->district_code) {
                $this->regionNames = $store->only('province', 'city', 'district');

                return;
            }
            $directory = app(RegionDirectory::class);
            $parent = null;
            try {
                foreach (['province' => 'provinces', 'city' => 'cities', 'district' => 'districts'] as $field => $level) {
                    $code = $this->input($field.'_code');
                    if (! $code) {
                        if ($this->boolean('is_listed')) {
                            $validator->errors()->add($field.'_code', 'Please select this region before publishing.');
                        }
                        break;
                    }
                    $region = collect($directory->list($level, $parent))->firstWhere('id', $code);
                    if (! $region) {
                        $validator->errors()->add($field.'_code', 'The selected region does not belong to its parent.');

                        return;
                    }
                    $this->regionNames[$field] = $region['name'];
                    $parent = $code;
                }
            } catch (Throwable) {
                $validator->errors()->add('province_code', 'Region data is temporarily unavailable. Please retry before saving these changes.');
            }
        }];
    }

    public function authorize(): bool
    {
        $store = $this->route('store');

        if ($store instanceof Store) {
            Gate::authorize('update', $store);
        }

        return $this->user() !== null;
    }

    public function rules(): array
    {
        $store = $this->route('store');

        return [
            'name' => ['required', 'string', 'max:100', function (string $attribute, mixed $value, Closure $fail) use ($store): void {
                if (is_string($value) && Store::query()->where('name_key', Store::nameKey($value))
                    ->when($store instanceof Store, fn ($query) => $query->whereKeyNot($store->id))->exists()) {
                    $fail('This store name is already in use. Please choose another name.');
                }
            }],
            'slug' => [
                'required', 'string', 'min:3', 'max:80', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('rintis_stores', 'slug')->ignore($store instanceof Store ? $store : null),
            ],
            'tagline' => ['nullable', 'string', 'max:180'],
            'is_listed' => ['required', 'boolean'],
            'category_id' => ['nullable', 'required_if:is_listed,true', 'integer', 'exists:rintis_store_categories,id'],
            'province' => ['nullable', 'required_if:is_listed,true', 'string', 'max:100'],
            'city' => ['nullable', 'required_if:is_listed,true', 'string', 'max:100'],
            'district' => ['nullable', 'required_if:is_listed,true', 'string', 'max:100'],
            'address_line' => ['nullable', 'required_if:is_listed,true', 'string', 'max:300'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'show_full_address' => ['sometimes', 'boolean'],
            'province_code' => ['nullable', 'required_with:city_code,district_code', 'string', 'regex:/^[0-9]{2}$/'],
            'city_code' => ['nullable', 'required_with:district_code', 'string', 'regex:/^[0-9]{2}\.[0-9]{2}$/'],
            'district_code' => ['nullable', 'string', 'regex:/^[0-9]{2}\.[0-9]{2}\.[0-9]{2}$/'],
            'pickup_enabled' => ['sometimes', 'boolean'],
            'pickup_instructions' => ['nullable', 'string', 'max:1000'],
            'priority_enabled' => ['sometimes', 'boolean'],
            'priority_fee' => [Rule::requiredIf(fn () => $this->boolean('priority_enabled')), 'nullable', 'integer', 'min:0', 'max:9999999'],
            'priority_minutes' => [Rule::requiredIf(fn () => $this->boolean('priority_enabled')), 'nullable', 'integer', 'min:1', 'max:1440'],
            'hemat_enabled' => ['sometimes', 'boolean'],
            'hemat_fee' => [Rule::requiredIf(fn () => $this->boolean('hemat_enabled')), 'nullable', 'integer', 'min:0', 'max:9999999'],
            'hemat_minutes' => [Rule::requiredIf(fn () => $this->boolean('hemat_enabled')), 'nullable', 'integer', 'min:1', 'max:1440'],
            'delivery_enabled' => ['sometimes', 'boolean'],
            'delivery_fee_mode' => ['nullable', 'required_if:delivery_enabled,true', Rule::in(['fixed', 'road_distance'])],
            'delivery_fixed_fee' => ['nullable', Rule::requiredIf(fn () => $this->boolean('delivery_enabled') && $this->input('delivery_fee_mode') === 'fixed'), 'integer', 'min:0', 'max:999999999'],
            'delivery_rate_per_km' => ['nullable', Rule::requiredIf(fn () => $this->boolean('delivery_enabled') && $this->input('delivery_fee_mode') === 'road_distance'), 'integer', 'min:0', 'max:999999999'],
            'delivery_min_distance_m' => ['nullable', 'required_if:delivery_enabled,true', 'integer', 'min:1', 'max:1000000'],
            'delivery_max_distance_m' => ['nullable', 'required_if:delivery_enabled,true', 'integer', 'min:1', 'max:1000000'],
            'deleted_bank_account_ids' => ['sometimes', 'array', 'max:100'],
            'deleted_bank_account_ids.*' => ['required', 'integer', 'distinct', Rule::exists('rintis_bank_accounts', 'id')->where('store_id', $store instanceof Store ? $store->id : 0)],
            'cash_enabled' => ['sometimes', 'required', 'boolean'],
            'bank_transfer_enabled' => ['sometimes', 'required', 'boolean'],
            'bank_accounts' => ['sometimes', 'array', 'max:10'],
            'bank_accounts.*' => ['array:id,bank_name,account_holder,account_number,instructions,is_active'],
            'bank_accounts.*.id' => ['nullable', 'integer', 'distinct', Rule::exists('rintis_bank_accounts', 'id')->where('store_id', $store instanceof Store ? $store->id : 0)],
            'bank_accounts.*.bank_name' => ['required', 'string', 'max:100'],
            'bank_accounts.*.account_holder' => ['required', 'string', 'max:150'],
            'bank_accounts.*.account_number' => ['required', 'string', 'regex:/^[0-9]{5,34}$/'],
            'bank_accounts.*.instructions' => ['nullable', 'string', 'max:500'],
            'bank_accounts.*.is_active' => ['required', 'boolean'],
        ];
    }
}
