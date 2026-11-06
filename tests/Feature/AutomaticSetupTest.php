<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AutomaticSetupTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_adding_first_visible_product_automatically_finishes_setup_and_does_not_reset_it(): void
    {
        $store = Store::factory()->create(['latitude' => 0.9, 'longitude' => 109, 'pickup_enabled' => true]);
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $data = ['name' => 'Latte', 'price' => 17000, 'catalog_category_id' => $category->id, 'is_visible' => true];

        $this->actingAs($store->owner)->post(route('admin.catalog.store', $store), $data)->assertSessionHasNoErrors()->assertRedirectToRoute('admin.catalog.index', $store);

        $completedAt = $store->fresh()->setup_completed_at;
        $this->assertNotNull($completedAt);
        $this->get(route('admin.stores.show', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('setupCompleted', true)->missing('completeSetupUrl'));
        $product = $store->catalogProducts()->sole();
        $this->patch(route('admin.catalog.update', [$store, $product]), [...$data, 'is_visible' => false])->assertSessionHasNoErrors();
        $this->assertSame($completedAt->toISOString(), $store->fresh()->setup_completed_at->toISOString());
    }

    public function test_saving_settings_last_automatically_finishes_setup(): void
    {
        $store = Store::factory()->create();
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $store->catalogProducts()->create(['name' => 'Latte', 'price' => 17000, 'catalog_category_id' => $category->id, 'is_visible' => true]);
        $data = [...$store->only('name', 'slug'), 'latitude' => 0.9, 'longitude' => 109, 'pickup_enabled' => true];

        $this->actingAs($store->owner)->patch(route('admin.stores.update', $store), $data)->assertSessionHasNoErrors();

        $this->assertNotNull($store->fresh()->setup_completed_at);
    }

    public function test_incomplete_settings_block_setup_and_saved_products_are_always_visible(): void
    {
        $store = Store::factory()->create();
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $data = ['name' => 'Latte', 'price' => 17000, 'catalog_category_id' => $category->id, 'is_visible' => true];
        $this->actingAs($store->owner)->post(route('admin.catalog.store', $store), $data)->assertSessionHasNoErrors();
        $this->assertNull($store->fresh()->setup_completed_at);
        $product = $store->catalogProducts()->sole();
        $this->patch(route('admin.catalog.update', [$store, $product]), [...$data, 'is_visible' => false])->assertSessionHasNoErrors();
        $this->patch(route('admin.stores.update', $store), [...$store->only('name', 'slug'), 'latitude' => 0.9, 'longitude' => 109, 'pickup_enabled' => true])->assertSessionHasNoErrors();
        $this->assertNotNull($store->fresh()->setup_completed_at);
        $this->assertTrue($product->fresh()->is_visible);
        $this->patch(route('admin.catalog.update', [$store, $product]), $data)->assertSessionHasNoErrors();
        $this->assertNotNull($store->fresh()->setup_completed_at);
    }

    public function test_standalone_workspace_does_not_share_central_navigation(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->get(route('admin.stores.show', $store))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->missing('centralAdminNavigation')->missing('access'));
    }
}
