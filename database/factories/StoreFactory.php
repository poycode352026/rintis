<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Store> */
class StoreFactory extends Factory
{
    public function definition(): array
    {
        return [
            'owner_id' => User::factory(),
            'name' => fake()->unique()->company(),
            'slug' => fake()->unique()->bothify('store-????????????'),
            'tagline' => null,
            'is_listed' => false,
            'category_id' => StoreCategory::factory(),
            'province' => 'Kalimantan Barat',
            'city' => 'Singkawang',
            'district' => 'Singkawang Barat',
            'address_line' => 'Test address',
            'show_full_address' => false,
        ];
    }
}
