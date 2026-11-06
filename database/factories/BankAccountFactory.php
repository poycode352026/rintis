<?php

namespace Database\Factories;

use App\Models\BankAccount;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BankAccount> */
class BankAccountFactory extends Factory
{
    public function definition(): array
    {
        return ['store_id' => Store::factory(), 'bank_name' => 'Test Bank', 'account_holder' => 'Test Owner', 'account_number' => '0012345678', 'is_active' => true];
    }
}
