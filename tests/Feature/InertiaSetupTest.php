<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaSetupTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_home_opens_rintis_and_guests_can_explore(): void
    {
        $this->get('/')->assertRedirectToRoute('directory');
        $this->get(route('directory'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Directory'));
    }

    public function test_existing_and_new_accounts_open_store_setup_without_central_activation(): void
    {
        $owner = User::factory()->create(['is_platform_owner' => true]);
        $this->actingAs($owner)->get('/central/admin')->assertNotFound();
        $this->get(route('account'))->assertRedirectToRoute('admin.index');
        $this->actingAs(User::factory()->create())->get(route('account'))->assertRedirectToRoute('admin.index');
        $this->get(route('home'))->assertRedirectToRoute('admin.index');
        $this->get(route('admin.stores.create'))->assertOk();
    }

    public function test_previously_shared_store_and_receipt_links_redirect_to_standalone_urls(): void
    {
        $this->get('/rintis/stores/wemeet-coffee?tab=menu')->assertRedirect('/stores/wemeet-coffee?tab=menu');
        $this->get('/rintis/orders/example-private-token')->assertRedirect('/orders/example-private-token');
    }
}
