<?php

namespace Database\Factories;

use App\Models\CatalogCategory;
use App\Models\CatalogProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CatalogProduct> */
class CatalogProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'catalog_category_id' => CatalogCategory::factory(),
            'store_id' => fn (array $attributes) => CatalogCategory::findOrFail($attributes['catalog_category_id'])->store_id,
            'name' => fake()->words(2, true), 'description' => fake()->sentence(),
            'price' => 18000, 'is_visible' => false,
        ];
    }
}
