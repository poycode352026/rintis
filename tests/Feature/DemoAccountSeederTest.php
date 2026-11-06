<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoAccountSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DemoAccountSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_default_seeding_creates_owner_and_is_safe_to_repeat(): void
    {
        config(['rintis.seed_account.password' => 'owner-seed-test-password']);
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);
        $owner = User::query()->where('email', 'oneclickstore@gmail.com')->sole();
        $this->assertSame('OCS', $owner->name);
        $this->assertFalse((bool) $owner->is_platform_owner);
        $this->assertTrue(Hash::check('owner-seed-test-password', $owner->password));
        $this->assertNotSame('owner-seed-test-password', $owner->getRawOriginal('password'));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_existing_account_is_updated_without_replacing_identity_or_other_users(): void
    {
        config(['rintis.seed_account.password' => 'owner-seed-test-password']);
        $owner = User::factory()->create(['email' => 'oneclickstore@gmail.com']);
        $other = User::factory()->create();
        $this->seed(DemoAccountSeeder::class);
        $this->assertSame($owner->id, User::query()->where('email', 'oneclickstore@gmail.com')->sole()->id);
        $this->assertFalse((bool) $owner->fresh()->is_platform_owner);
        $this->assertTrue(Hash::check('owner-seed-test-password', $owner->fresh()->password));
        $this->assertFalse((bool) $other->fresh()->is_platform_owner);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_missing_seed_password_does_not_create_an_owner(): void
    {
        config(['rintis.seed_account.password' => null]);
        try {
            $this->seed(DemoAccountSeeder::class);
            $this->fail('Missing password should prevent seeding.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('RINTIS_SEED_ACCOUNT_PASSWORD', $exception->getMessage());
        }
        $this->assertDatabaseCount('users', 0);
    }
}
