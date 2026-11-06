<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\StoreCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StoreLocationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function data(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Wemeet Coffee', 'slug' => 'wemeet-coffee', 'tagline' => null,
            'is_listed' => false, 'category_id' => StoreCategory::query()->where('slug', 'coffee-drinks')->value('id'),
            'province' => 'Kalimantan Barat', 'city' => 'Singkawang', 'district' => 'Singkawang Barat',
            'address_line' => 'Private home address', 'latitude' => '0.9001234', 'longitude' => '108.9001234',
            'show_full_address' => false,
        ], $overrides);
    }

    public function test_category_and_location_are_saved_for_the_authenticated_owner(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('admin.stores.store'), $this->data())->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rintis_stores', [
            'owner_id' => $owner->id, 'city' => 'Singkawang', 'province' => 'Kalimantan Barat',
            'address_line' => 'Private home address', 'show_full_address' => false,
        ]);
        $store = $owner->store;
        $this->assertSame('0.9001234', $store->latitude);
        $this->assertSame('108.9001234', $store->longitude);
        $this->get(route('admin.stores.edit', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('store.latitude', '0.9001234')->where('store.city', 'Singkawang')->has('categories', 7));
    }

    public function test_store_creation_requires_location_and_category_even_if_client_requests_draft(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.stores.store'), [
            'name' => 'Draft Store', 'slug' => 'draft-store', 'is_listed' => false,
        ])->assertSessionHasErrors(['category_id', 'province', 'city', 'district', 'address_line']);

        $this->assertDatabaseCount('rintis_stores', 0);
    }

    public function test_publishing_requires_category_and_address_fields(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('admin.stores.store'), [
            'name' => 'Incomplete', 'slug' => 'incomplete', 'is_listed' => true,
        ])->assertSessionHasErrors(['category_id', 'province', 'city', 'district', 'address_line']);

        $this->assertDatabaseCount('rintis_stores', 0);
    }

    #[TestWith(['latitude', 91])]
    #[TestWith(['latitude', -91])]
    #[TestWith(['longitude', 181])]
    #[TestWith(['longitude', -181])]
    #[TestWith(['latitude', 'invalid'])]
    #[TestWith(['category_id', 999999])]
    #[TestWith(['province', 101])]
    #[TestWith(['city', 101])]
    #[TestWith(['district', 101])]
    #[TestWith(['address_line', 301])]
    public function test_invalid_location_details_do_not_create_a_store(string $field, mixed $value): void
    {
        $owner = User::factory()->create();
        if (in_array($field, ['province', 'city', 'district', 'address_line'], true)) {
            $value = str_repeat('a', $value);
        }

        $this->actingAs($owner)->post(route('admin.stores.store'), $this->data([$field => $value]))->assertSessionHasErrors($field);

        $this->assertDatabaseCount('rintis_stores', 0);
    }

    #[TestWith(['latitude', 'longitude'])]
    #[TestWith(['longitude', 'latitude'])]
    public function test_coordinates_must_be_supplied_together(string $missing, string $present): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->post(route('admin.stores.store'), $this->data([$missing => null, $present => 0]))
            ->assertSessionHasErrors($missing);
        $this->assertDatabaseCount('rintis_stores', 0);
    }

    public function test_zero_coordinates_are_valid_and_can_be_cleared(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($owner)->post(route('admin.stores.store'), $this->data(['latitude' => 0, 'longitude' => 0]))->assertSessionHasNoErrors();
        $store = $owner->store;
        $this->assertSame('0.0000000', $store->latitude);

        $this->patch(route('admin.stores.update', $store), $this->data(['latitude' => null, 'longitude' => null]))->assertSessionHasNoErrors();
        $this->assertNull($store->fresh()->latitude);
        $this->assertNull($store->fresh()->longitude);
    }

    public function test_public_pages_do_not_include_private_address_or_coordinates(): void
    {
        $store = Store::factory()->create($this->data(['is_listed' => true]));

        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('store.city', 'Singkawang')->where('store.category', 'Coffee & Drinks')
            ->missing('store.address_line')->missing('store.district')->missing('store.latitude')->missing('store.longitude'));
        $this->get(route('directory'))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('stores.data.0.city', 'Singkawang')->where('stores.data.0.category', 'Coffee & Drinks')
            ->missing('stores.data.0.address_line')->missing('stores.data.0.latitude')->missing('stores.data.0.longitude'));
    }

    public function test_legacy_public_address_flag_never_exposes_exact_location(): void
    {
        $store = Store::factory()->create($this->data(['is_listed' => true, 'show_full_address' => true]));

        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->missing('store.address_line')->missing('store.latitude')->missing('store.longitude')->missing('store.district'));
        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $this->data(['is_listed' => true]))->assertSessionHasNoErrors();
        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->missing('store.address_line')->missing('store.latitude'));
    }

    public function test_another_owner_cannot_read_or_change_private_location(): void
    {
        $store = Store::factory()->create($this->data());
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('admin.stores.edit', $store))->assertNotFound();
        $this->patch(route('admin.stores.update', $store), $this->data(['address_line' => 'Changed']))->assertNotFound();
        $this->assertDatabaseHas('rintis_stores', ['id' => $store->id, 'address_line' => 'Private home address']);
    }

    public function test_client_cannot_enable_public_address_sharing(): void
    {
        $this->actingAs(User::factory()->create())->post(route('admin.stores.store'), $this->data(['show_full_address' => 'yes']))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rintis_stores', ['name' => 'Wemeet Coffee', 'show_full_address' => false]);
    }
}
