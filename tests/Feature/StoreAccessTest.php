<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StoreAccessTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function storeData(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Wemeet Coffee', 'slug' => 'wemeet-coffee',
            'tagline' => 'Coffee for your day.', 'is_listed' => false,
            'category_id' => StoreCategory::query()->where('slug', 'coffee-drinks')->value('id'),
            'province' => 'Kalimantan Barat', 'city' => 'Singkawang', 'district' => 'Singkawang Barat',
            'address_line' => 'Test address', 'show_full_address' => false,
        ], $overrides);
    }

    public function test_guest_cannot_open_or_change_store_management(): void
    {
        $store = Store::factory()->create();

        $this->get(route('admin.index'))->assertRedirectToRoute('login');
        $this->get(route('admin.stores.create'))->assertRedirectToRoute('login');
        $this->get(route('admin.stores.show', $store))->assertRedirectToRoute('login');
        $this->get(route('admin.stores.edit', $store))->assertRedirectToRoute('login');
        $this->post(route('admin.stores.store'), $this->storeData())->assertRedirectToRoute('login');
        $this->patch(route('admin.stores.update', $store), $this->storeData())->assertRedirectToRoute('login');
        $this->assertDatabaseCount('rintis_stores', 1);
        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'name' => $store->name]);
    }

    public function test_new_account_is_directed_to_store_setup(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('admin.index'))->assertRedirectToRoute('admin.stores.create');
        $this->get(route('admin.stores.create'))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/StoreForm')->where('store', null));
    }

    public function test_store_is_owned_by_authenticated_user_and_cannot_be_assigned_to_another_account(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($owner)->post(route('admin.stores.store'), $this->storeData(['owner_id' => $other->id]));

        $response->assertRedirectToRoute('admin.stores.show', ['store' => 'wemeet-coffee']);
        $this->assertDatabaseHas('rintis_stores', ['owner_id' => $owner->id, 'name' => 'Wemeet Coffee', 'is_listed' => true]);
        $this->assertDatabaseMissing('rintis_stores', ['owner_id' => $other->id]);
    }

    public function test_owner_cannot_create_a_second_store(): void
    {
        $store = Store::factory()->create();

        $this->actingAs($store->owner)->post(route('admin.stores.store'), $this->storeData())
            ->assertRedirectToRoute('admin.stores.show', $store);

        $this->assertDatabaseCount('rintis_stores', 1);
        $this->get(route('admin.stores.create'))->assertRedirectToRoute('admin.index');
    }

    public function test_owner_can_view_only_their_store_overview_and_settings(): void
    {
        $store = Store::factory()->create();
        $other = Store::factory()->create();
        $this->actingAs($store->owner);

        $this->get(route('admin.index'))->assertRedirectToRoute('admin.stores.show', $store);
        $this->get(route('admin.stores.show', $store))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Overview')->where('store.name', $store->name));
        $this->get(route('admin.stores.edit', $store))->assertOk();
        $this->get(route('admin.stores.show', $other))->assertNotFound();
        $this->get(route('admin.stores.edit', $other))->assertNotFound();
    }

    public function test_other_owner_cannot_update_store_or_steal_ownership(): void
    {
        $store = Store::factory()->create();
        $attacker = User::factory()->create();

        $this->actingAs($attacker)->patch(route('admin.stores.update', $store), $this->storeData(['owner_id' => $attacker->id]))
            ->assertNotFound();

        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'name' => $store->name, 'owner_id' => $store->owner_id]);
    }

    public function test_owner_can_update_identity_without_unpublishing_or_transferring_ownership(): void
    {
        $store = Store::factory()->create();
        $other = User::factory()->create();
        $this->actingAs($store->owner);

        $this->patch(route('admin.stores.update', $store), $this->storeData(['owner_id' => $other->id, 'is_listed' => true]))
            ->assertRedirectToRoute('admin.stores.show', ['store' => 'wemeet-coffee'])
            ->assertSessionHas('status', 'Store settings saved.');

        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'owner_id' => $store->owner_id, 'slug' => 'wemeet-coffee', 'is_listed' => true]);
        $store->refresh();
        $this->patch(route('admin.stores.update', $store), $this->storeData())->assertRedirect();
        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'is_listed' => true]);
    }

    #[TestWith(['name', ''])]
    #[TestWith(['name', 101])]
    #[TestWith(['slug', 'ab'])]
    #[TestWith(['slug', 81])]
    #[TestWith(['tagline', 181])]
    public function test_invalid_store_details_are_not_saved(string $field, string|int $value): void
    {
        $this->actingAs(User::factory()->create());
        $value = is_int($value) ? str_repeat('a', $value) : $value;

        $this->post(route('admin.stores.store'), $this->storeData([$field => $value]))->assertSessionHasErrors($field);

        $this->assertDatabaseCount('rintis_stores', 0);
    }

    public function test_duplicate_slug_is_rejected_and_owner_can_keep_their_own_slug(): void
    {
        $store = Store::factory()->create(['slug' => 'wemeet-coffee']);
        $other = Store::factory()->create();

        $this->actingAs($other->owner)->patch(route('admin.stores.update', $other), $this->storeData())->assertSessionHasErrors('slug');
        $this->assertDatabaseHas('rintis_stores', ['id' => $other->id, 'slug' => $other->slug]);
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->storeData())->assertSessionHasNoErrors();
    }

    public function test_public_directory_lists_only_published_store_identity(): void
    {
        $listed = Store::factory()->create(['is_listed' => true]);
        Store::factory()->create(['is_listed' => false]);

        $this->get(route('directory'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Directory')->has('stores.data', 1)
            ->where('stores.data.0.name', $listed->name)
            ->missing('stores.data.0.owner_id')->missing('stores.data.0.email'));
    }

    public function test_unlisted_store_is_hidden_from_guests_and_other_owners_but_can_be_previewed_by_owner(): void
    {
        $store = Store::factory()->create();
        $url = route('storefront', $store);

        $this->get($url)->assertNotFound();
        $this->actingAs(User::factory()->create())->get($url)->assertNotFound();
        $this->actingAs($store->owner)->get($url)->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Storefront/Home')->where('isPreview', true));
    }

    public function test_published_store_is_public_without_owner_or_account_details(): void
    {
        $store = Store::factory()->create(['is_listed' => true]);

        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Storefront/Home')->where('store.name', $store->name)
            ->where('isPreview', false)->where('auth.user', null)->missing('store.owner_id')->missing('store.email'));
    }

    #[TestWith(['Upper Case', 'upper-case'])]
    #[TestWith(['../admin', 'admin'])]
    #[TestWith(['-leading', 'leading'])]
    #[TestWith(['trailing-', 'trailing'])]
    public function test_store_url_is_normalized_before_saving(string $input, string $expected): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('admin.stores.store'), $this->storeData(['slug' => $input]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rintis_stores', ['slug' => $expected]);
    }

    public function test_client_cannot_disable_automatic_store_listing(): void
    {
        $this->actingAs(User::factory()->create());
        $this->post(route('admin.stores.store'), $this->storeData(['is_listed' => 'invalid']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rintis_stores', ['name' => 'Wemeet Coffee', 'is_listed' => true]);
    }

    public function test_overview_displays_paginated_own_menu_without_deleted_or_foreign_products(): void
    {
        $store = Store::factory()->create();
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        for ($index = 1; $index <= 13; $index++) {
            $store->catalogProducts()->create(['name' => sprintf('Menu %02d', $index), 'description' => 'A drink', 'catalog_category_id' => $category->id, 'image_path' => 'coffee.jpg', 'price' => 17000, 'costing' => ['secret' => 'recipe']]);
        }
        $deleted = $store->catalogProducts()->create(['name' => 'Deleted product', 'catalog_category_id' => $category->id, 'price' => 100]);
        $deleted->delete();
        $other = Store::factory()->create();
        $foreignCategory = $other->catalogCategories()->create(['name' => 'Food']);
        $other->catalogProducts()->create(['name' => 'Foreign product', 'catalog_category_id' => $foreignCategory->id, 'price' => 100]);
        $this->actingAs($store->owner)->get(route('admin.stores.show', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('products.total', 13)->has('products.data', 12)->where('products.data.0.name', 'Menu 01')->where('products.data.0.description', 'A drink')
            ->where('products.data.0.imageUrl', fn ($url) => str_ends_with($url, '/coffee.jpg'))->has('products.data.0.editUrl')->has('createProductUrl')->missing('products.data.0.costing')->missing('products.data.0.options'));
        $this->get(route('admin.stores.show', $store).'?page=2')->assertOk()->assertInertia(fn (Assert $page) => $page->has('products.data', 1)->where('products.data.0.name', 'Menu 13'));
    }
}
