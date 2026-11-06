<?php

namespace Tests\Feature;

use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActiveProductLockTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_and_scheduled_products_cannot_be_edited_or_deleted_and_unlock_after_close(): void
    {
        $this->freezeTime();
        $store = Store::factory()->create();
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $product = $store->catalogProducts()->create(['name' => 'Coffee', 'price' => 17000, 'description' => 'Original', 'catalog_category_id' => $category->id]);
        $period = $store->openOrders()->create(['name' => 'Session', 'status' => 'published', 'opens_at' => now()->addHour(), 'closes_at' => now()->addHours(2), 'fulfillment' => []]);
        $period->items()->create(['catalog_product_id' => $product->id, 'name' => 'Coffee', 'price' => 17000]);
        $this->actingAs($store->owner);
        $this->get(route('admin.catalog.index', $store))->assertInertia(fn (Assert $page) => $page->where('products.data.0.isLocked', true)->where('products.data.0.editUrl', null)->where('products.data.0.deleteUrl', null));
        $this->get(route('admin.catalog.edit', [$store, $product]))->assertRedirect(route('admin.catalog.index', $store));
        $data = ['name' => 'Changed', 'price' => 1000, 'description' => 'Changed', 'catalog_category_id' => $category->id];
        $this->patch(route('admin.catalog.update', [$store, $product]), $data)->assertSessionHasErrors('product');
        $this->delete(route('admin.catalog.destroy', [$store, $product]))->assertSessionHasErrors('product');
        $this->assertSame(17000, $product->fresh()->price);
        $this->assertNull($product->fresh()->deleted_at);
        $period->update(['status' => 'closed']);
        $this->patch(route('admin.catalog.update', [$store, $product]), $data)->assertSessionHasNoErrors();
        $this->assertSame(1000, $product->fresh()->price);
        $this->assertSame(17000, $period->items()->sole()->price);
    }

    public function test_unrelated_products_and_ended_sessions_do_not_block_product_editing(): void
    {
        $store = Store::factory()->create();
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $product = $store->catalogProducts()->create(['name' => 'Coffee', 'price' => 17000, 'catalog_category_id' => $category->id]);
        $period = $store->openOrders()->create(['name' => 'Session', 'status' => 'published', 'opens_at' => now()->subHours(2), 'closes_at' => now()->subHour(), 'fulfillment' => []]);
        $period->items()->create(['catalog_product_id' => $product->id, 'name' => 'Coffee', 'price' => 17000]);
        $this->actingAs($store->owner)->get(route('admin.catalog.edit', [$store, $product]))->assertOk();
        $this->get(route('admin.catalog.index', $store))->assertInertia(fn (Assert $page) => $page->where('products.data.0.isLocked', false));
    }
}
