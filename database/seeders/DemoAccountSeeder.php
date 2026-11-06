<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('rintis.seed_account.password');
        if (! is_string($password) || mb_strlen($password) < 12) {
            throw new RuntimeException('Set RINTIS_SEED_ACCOUNT_PASSWORD to at least 12 characters before seeding.');
        }

        $user = User::query()->firstOrNew(['email' => config('rintis.seed_account.email')]);
        $user->forceFill([
            'name' => config('rintis.seed_account.name'),
            'password' => $password,
        ])->save();
    }
}
