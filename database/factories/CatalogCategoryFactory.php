<?php

namespace Database\Factories;

use App\Models\CatalogCategory;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CatalogCategory> */
class CatalogCategoryFactory extends Factory
{
    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'name' => fake()->unique()->word()];
    }
}
