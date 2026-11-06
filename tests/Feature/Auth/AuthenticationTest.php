<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_can_open_account_forms(): void
    {
        $this->get(route('login'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Login'));
        $this->get(route('register'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Auth/Register'));
    }

    public function test_registration_normalizes_email_hashes_password_and_can_start_a_store(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Store Owner',
            'email' => 'OWNER@EXAMPLE.COM',
            'password' => 'a-long-test-password',
            'password_confirmation' => 'a-long-test-password',
            'is_platform_owner' => true,
        ]);

        $response->assertRedirectToRoute('account');
        $user = User::query()->sole();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('owner@example.com', $user->email);
        $this->assertTrue(Hash::check('a-long-test-password', $user->password));
        $this->assertFalse((bool) $user->is_platform_owner);
        $this->assertDatabaseCount('rintis_stores', 0);
        $this->get(route('account'))->assertRedirectToRoute('admin.index');
        $this->get(route('admin.stores.create'))->assertOk();
    }

    public function test_invalid_registration_creates_no_account(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => '', 'email' => 'invalid', 'password' => 'short', 'password_confirmation' => 'different',
        ]);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
        $this->assertDatabaseCount('users', 0);
        $this->assertGuest();
    }

    public function test_registration_rejects_existing_email_case_insensitively(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post(route('register.store'), [
            'name' => 'Duplicate', 'email' => 'EXISTING@EXAMPLE.COM',
            'password' => 'a-long-test-password', 'password_confirmation' => 'a-long-test-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
    }

    public function test_owner_can_sign_in_with_normalized_email(): void
    {
        $user = User::factory()->create(['email' => 'login@example.com']);

        $response = $this->post(route('login.store'), ['email' => 'LOGIN@EXAMPLE.COM', 'password' => 'password']);

        $response->assertRedirectToRoute('account');
        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_do_not_authenticate(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);

        $response->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
        $this->assertGuest();
    }

    public function test_login_requires_valid_email_and_password(): void
    {
        $this->post(route('login.store'), [])->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_login_attempts_are_throttled_and_recover_after_the_timeout(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login.store'), ['email' => $user->email, 'password' => 'wrong']);
        }

        $response = $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password']);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->travel(61)->seconds();
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'password'])
            ->assertRedirectToRoute('account');
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_users_are_redirected_away_from_account_forms(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('login'))->assertRedirectToRoute('account');
        $this->get(route('register'))->assertRedirectToRoute('account');
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('logout'))->assertRedirectToRoute('login');

        $this->assertGuest();
        $this->get(route('admin.index'))->assertRedirectToRoute('login');
    }
}
