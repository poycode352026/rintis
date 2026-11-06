<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class WeeklyScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('store'));

        return true;
    }

    protected function prepareForValidation(): void
    {
        // Older clients supplied one menu for the whole week.
        if (is_array($this->input('days')) && $this->has('product_ids')) {
            $this->merge(['days' => array_map(fn ($day) => is_array($day) && ! array_key_exists('product_ids', $day) ? [...$day, 'product_ids' => $this->input('product_ids')] : $day, $this->input('days'))]);
        }
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'days' => ['required', 'array', 'size:7', 'list'],
            'days.*' => ['array:enabled,opens,closes,product_ids,delivery_starts,delivery_ends'],
            'days.*.enabled' => ['required', 'boolean'],
            'days.*.opens' => ['required', 'date_format:H:i'],
            'days.*.closes' => ['required', 'date_format:H:i'],
            'days.*.delivery_starts' => ['nullable', 'date_format:H:i'],
            'days.*.delivery_ends' => ['nullable', 'date_format:H:i'],
            'days.*.product_ids' => ['present', 'array', 'max:100', 'list'],
            'days.*.product_ids.*' => ['required', 'integer', Rule::exists('rintis_catalog_products', 'id')->where('store_id', $this->route('store')->id)->whereNull('deleted_at')],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            foreach (is_array($this->input('days')) ? $this->input('days') : [] as $index => $day) {
                if (! is_array($day)) {
                    continue;
                }
                if (! empty($day['enabled']) && ($day['closes'] ?? '') <= ($day['opens'] ?? '')) {
                    $validator->errors()->add("days.$index.closes", 'Closing time must be after opening time on the same day.');
                }
                if (! empty($day['enabled']) && ! empty($day['delivery_starts']) && (($day['delivery_starts'] < ($day['closes'] ?? '')) || empty($day['delivery_ends']) || $day['delivery_ends'] <= $day['delivery_starts'])) {
                    $validator->errors()->add("days.$index.delivery_ends", 'Delivery must start after closing and end after it starts. Use a manual session for overnight delivery.');
                }
                $ids = $day['product_ids'] ?? [];
                if ($this->boolean('enabled') && ! empty($day['enabled']) && $ids === []) {
                    $validator->errors()->add("days.$index.product_ids", 'Choose at least one product for this day or mark it Closed.');
                }
                if (is_array($ids) && count(array_unique(array_filter($ids, 'is_scalar'))) !== count($ids)) {
                    $validator->errors()->add("days.$index.product_ids", 'Select each product only once per day.');
                }
            }
        }];
    }
}
