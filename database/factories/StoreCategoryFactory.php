<?php

namespace Database\Factories;

use App\Models\StoreCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StoreCategory> */
class StoreCategoryFactory extends Factory
{
    protected $model = StoreCategory::class;

    public function definition(): array
    {
        return ['name' => fake()->words(2, true), 'slug' => fake()->unique()->slug(2)];
    }
}
