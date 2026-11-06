<?php

namespace App\Http\Requests;

use App\Services\ProductCostCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use JsonException;

class CatalogProductRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['is_visible' => true]);
        if (is_string($this->input('options'))) {
            try {
                $this->merge(['options' => json_decode($this->input('options'), true, 32, JSON_THROW_ON_ERROR)]);
            } catch (JsonException) {
                $this->merge(['options' => 'invalid']);
            }
        }
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $groups = [];
            foreach ($this->input('options', []) as $index => $group) {
                $key = Str::lower(Str::squish($group['name']));
                if (in_array($key, $groups, true)) {
                    $validator->errors()->add('options.'.$index.'.name', 'Each option group needs a unique name.');
                }
                $groups[] = $key;
                $names = [];
                foreach ($group['choices'] as $choiceIndex => $choice) {
                    $key = Str::lower(Str::squish($choice['name']));
                    if (in_array($key, $names, true)) {
                        $validator->errors()->add('options.'.$index.'.choices.'.$choiceIndex.'.name', 'Each choice in this group needs a unique name.');
                    }
                    $names[] = $key;
                }
            }
            if (! is_array($this->input('costing'))) {
                return;
            }
            try {
                app(ProductCostCalculator::class)->calculate($this->input('costing'));
            } catch (InvalidArgumentException $exception) {
                $validator->errors()->add('costing', $exception->getMessage());
            }
        }];
    }

    public function authorize(): bool
    {
        Gate::authorize('update', $this->route('store'));

        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999'],
            'catalog_category_id' => ['required', 'integer', Rule::exists('rintis_catalog_categories', 'id')->where('store_id', $this->route('store')->id)],
            'is_visible' => ['required', 'boolean'],
            'options' => ['sometimes', 'array', 'max:8'],
            'options.*' => ['array:id,name,required,selection,choices'],
            'options.*.id' => ['required', 'uuid', 'distinct'],
            'options.*.name' => ['required', 'string', 'max:80'],
            'options.*.required' => ['required', 'boolean'],
            'options.*.selection' => ['required', Rule::in(['single', 'multiple'])],
            'options.*.choices' => ['required', 'array', 'min:1', 'max:20'],
            'options.*.choices.*' => ['array:id,name,price_adjustment,cost_adjustment'],
            'options.*.choices.*.id' => ['required', 'uuid', 'distinct'],
            'options.*.choices.*.name' => ['required', 'string', 'max:80'],
            'options.*.choices.*.price_adjustment' => ['required', 'integer', 'min:0', 'max:999999999'],
            'options.*.choices.*.cost_adjustment' => ['required', 'integer', 'min:0', 'max:999999999'],
            'costing' => ['sometimes', 'nullable', 'array:batch_yield,additional_cost,target_margin,target_markup,ingredients'],
            'costing.batch_yield' => ['required_with:costing', 'integer', 'min:1', 'max:1000000'],
            'costing.additional_cost' => ['required_with:costing', 'integer', 'min:0', 'max:999999999'],
            'costing.target_markup' => ['nullable', 'numeric', 'min:0', 'max:1000000', 'regex:/^[0-9]+(?:\.[0-9]{1,2})?$/', 'prohibits:costing.target_margin'],
            'costing.target_margin' => ['nullable', 'numeric', 'min:0', 'max:99.99', 'regex:/^[0-9]+(?:\.[0-9]{1,2})?$/'],
            'costing.ingredients' => ['required_with:costing', 'array', 'min:1', 'max:50'],
            'costing.ingredients.*' => ['array:name,purchase_quantity,purchase_unit,purchase_cost,used_quantity,used_unit'],
            'costing.ingredients.*.name' => ['required', 'string', 'max:100'],
            'costing.ingredients.*.purchase_quantity' => ['required', 'numeric', 'min:0.001', 'max:1000', 'regex:/^[0-9]+(?:\.[0-9]{1,3})?$/'],
            'costing.ingredients.*.used_quantity' => ['required', 'numeric', 'min:0.001', 'max:1000', 'regex:/^[0-9]+(?:\.[0-9]{1,3})?$/'],
            'costing.ingredients.*.purchase_unit' => ['required', Rule::in(['g', 'kg', 'ml', 'l', 'pcs'])],
            'costing.ingredients.*.used_unit' => ['required', Rule::in(['g', 'kg', 'ml', 'l', 'pcs'])],
            'costing.ingredients.*.purchase_cost' => ['required', 'integer', 'min:0', 'max:999999999'],
            'photo' => ['nullable', 'prohibited_if:remove_image,true', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}
