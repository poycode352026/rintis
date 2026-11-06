<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SellerWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function data(Store $store, array $overrides = []): array
    {
        return array_replace($store->only('name', 'slug', 'category_id', 'province', 'city', 'district', 'address_line'), $overrides);
    }

    public function test_rintis_entry_sends_sellers_to_workspace_and_guests_to_public_directory(): void
    {
        $this->get(route('home'))->assertRedirectToRoute('directory');
        $seller = User::factory()->create();
        $this->actingAs($seller)->get(route('home'))->assertRedirectToRoute('admin.index');
        $this->get(route('directory'))->assertOk();
    }

    public function test_guest_can_browse_public_catalog_without_an_account_and_seller_can_browse_another_store(): void
    {
        $store = Store::factory()->create(['is_listed' => true, 'show_full_address' => true, 'latitude' => 0.9, 'longitude' => 109]);
        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('store.name', $store->name)->missing('store.address_line')->missing('store.latitude')->missing('store.longitude'));
        $seller = Store::factory()->create();
        $this->actingAs($seller->owner)->get(route('storefront', $store))->assertOk();
        $this->get(route('admin.stores.edit', $store))->assertNotFound();
    }

    public function test_name_uniqueness_ignores_case_and_extra_spaces_and_does_not_change_another_store(): void
    {
        $original = Store::factory()->create(['name' => 'Wemeet Coffee']);
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->patch(route('admin.stores.update', $other), $this->data($other, ['name' => '  WEMEET   coffee  ']))
            ->assertSessionHasErrors(['name' => 'This store name is already in use. Please choose another name.']);
        $this->assertSame($other->name, $other->fresh()->name);
        $this->assertSame('Wemeet Coffee', $original->fresh()->name);
    }

    public function test_store_owner_can_keep_own_name_and_slug_and_cannot_set_name_key(): void
    {
        $store = Store::factory()->create(['name' => 'Wemeet Coffee']);
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data($store, ['name_key' => 'forged']))->assertSessionHasNoErrors();
        $this->assertSame('wemeet coffee', $store->fresh()->name_key);
    }

    public function test_database_rejects_duplicate_normalized_store_name(): void
    {
        Store::factory()->create(['name' => 'Wemeet Coffee']);
        $this->expectException(UniqueConstraintViolationException::class);
        Store::factory()->create(['name' => ' WEMEET   Coffee ']);
    }

    public function test_live_availability_checks_both_identifiers_and_excludes_only_own_store(): void
    {
        $store = Store::factory()->create(['name' => 'Wemeet Coffee', 'slug' => 'wemeet-coffee']);
        $url = route('admin.availability', ['name' => '  WEMEET  coffee ', 'slug' => 'wemeet-coffee', 'store_id' => $store->id]);
        $this->actingAs(User::factory()->create())->getJson($url)->assertOk()
            ->assertExactJson(['nameAvailable' => false, 'slugAvailable' => false]);
        $this->actingAs($store->owner)->getJson($url)->assertOk()
            ->assertExactJson(['nameAvailable' => true, 'slugAvailable' => true]);
        $this->getJson(route('admin.availability', ['name' => 'Available store', 'slug' => 'available-store']))->assertOk()
            ->assertExactJson(['nameAvailable' => true, 'slugAvailable' => true]);
        $this->getJson(route('admin.availability', ['name' => 'Store', 'slug' => 'UPPER URL']))->assertUnprocessable();
    }

    public function test_availability_requires_login(): void
    {
        $url = route('admin.availability', ['name' => 'Store', 'slug' => 'store']);
        $this->get($url)->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create())->getJson($url)->assertOk();
    }

    public function test_settings_stage_groups_identity_location_and_fulfillment_without_requiring_a_bank(): void
    {
        $store = Store::factory()->create(['latitude' => 0.9, 'longitude' => 109, 'pickup_enabled' => true]);
        $this->actingAs($store->owner)->get(route('admin.stores.show', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('setup.settingsReady', true)->where('setup.categories', 0)->where('setup.bankAccounts', 0));
        $this->patch(route('admin.stores.setup', $store))->assertUnprocessable();
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $store->catalogProducts()->create(['name' => 'Latte', 'catalog_category_id' => $category->id, 'price' => 17000, 'is_visible' => true]);
        $this->patch(route('admin.stores.setup', $store))->assertRedirectToRoute('admin.stores.show', $store);
        $this->assertNotNull($store->fresh()->setup_completed_at);
    }

    public function test_disabled_fulfillment_or_missing_pin_leaves_settings_stage_incomplete(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->get(route('admin.stores.show', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('setup.settingsReady', false));
        $store->update(['latitude' => 0.9, 'longitude' => 109, 'pickup_enabled' => false, 'delivery_enabled' => true]);
        $this->get(route('admin.stores.show', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('setup.settingsReady', false));
    }

    public function test_new_store_is_automatically_public_and_exact_location_is_not_public(): void
    {
        $sample = Store::factory()->make();
        $sample->category_id = StoreCategory::query()->firstOrFail()->id;
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('admin.stores.store'), $this->data($sample, ['show_full_address' => true]))->assertSessionHasNoErrors();
        $store = $user->store;
        $this->assertTrue($store->is_listed);
        $this->assertFalse($store->show_full_address);
        $this->get(route('admin.stores.edit', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->missing('store.show_full_address')->has('availabilityUrl'));
    }
}
