<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductOptionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function optionGroups(): array
    {
        return [[
            'id' => (string) Str::uuid(), 'name' => 'Size', 'selection' => 'single', 'required' => true,
            'choices' => [
                ['id' => (string) Str::uuid(), 'name' => 'Medium', 'price_adjustment' => 0, 'cost_adjustment' => 0],
                ['id' => (string) Str::uuid(), 'name' => 'Large', 'price_adjustment' => 3000, 'cost_adjustment' => 1000],
            ],
        ]];
    }

    private function data(CatalogCategory $category, array $overrides = []): array
    {
        return array_replace(['name' => 'Americano', 'price' => 17000, 'catalog_category_id' => $category->id, 'options' => $this->optionGroups()], $overrides);
    }

    public function test_owner_can_save_choices_and_public_catalog_exposes_only_names_and_selection_rules(): void
    {
        $category = CatalogCategory::factory()->create();
        $store = $category->store;
        $store->update(['is_listed' => true]);
        $groups = $this->optionGroups();
        $this->actingAs($store->owner)->post(route('admin.catalog.store', $store), $this->data($category, ['options' => json_encode($groups)]))->assertSessionHasNoErrors();
        $product = $store->catalogProducts()->sole();
        $this->assertSame(3000, $product->options[0]['choices'][1]['price_adjustment']);
        $this->assertSame(1000, $product->options[0]['choices'][1]['cost_adjustment']);
        $this->assertArrayNotHasKey('options', $product->toArray());
        $this->get(route('admin.catalog.edit', [$store, $product]))->assertOk()->assertInertia(fn (Assert $page) => $page->where('product.options.0.choices.1.cost_adjustment', 1000));
        $this->post(route('logout'))->assertRedirect();
        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('products.data.0.options.0.name', 'Size')->where('products.data.0.options.0.choices.1.name', 'Large')
            ->missing('products.data.0.options.0.choices.1.cost_adjustment')->missing('products.data.0.options.0.choices.1.price_adjustment')->missing('products.data.0.costing'));
    }

    public function test_removing_all_choices_is_persisted_with_form_data_and_omitted_options_are_preserved(): void
    {
        $category = CatalogCategory::factory()->create();
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category))->assertSessionHasNoErrors();
        $product = $category->store->catalogProducts()->sole();
        $data = ['name' => $product->name, 'price' => $product->price, 'catalog_category_id' => $category->id];
        $this->patch(route('admin.catalog.update', [$category->store, $product]), $data)->assertSessionHasNoErrors();
        $this->assertCount(1, $product->fresh()->options);
        $this->patch(route('admin.catalog.update', [$category->store, $product]), [...$data, 'options' => '[]'])->assertSessionHasNoErrors();
        $this->assertSame([], $product->fresh()->options);
    }

    public function test_invalid_option_prices_duplicate_names_and_malformed_payload_are_rejected(): void
    {
        $category = CatalogCategory::factory()->create();
        $groups = $this->optionGroups();
        $groups[0]['choices'][1]['cost_adjustment'] = -1;
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category, ['options' => $groups]))->assertSessionHasErrors('options.0.choices.1.cost_adjustment');
        $groups = $this->optionGroups();
        $groups[0]['choices'][1]['name'] = ' MEDIUM ';
        $this->post(route('admin.catalog.store', $category->store), $this->data($category, ['options' => $groups]))->assertSessionHasErrors('options.0.choices.1.name');
        $groups = $this->optionGroups();
        $groups[0]['choices'] = [];
        $this->post(route('admin.catalog.store', $category->store), $this->data($category, ['options' => $groups]))->assertSessionHasErrors('options.0.choices');
        $this->post(route('admin.catalog.store', $category->store), $this->data($category, ['options' => '{broken']))->assertSessionHasErrors('options');
        $this->assertDatabaseCount('rintis_catalog_products', 0);
    }

    public function test_duplicate_groups_oversized_groups_and_untrusted_option_fields_are_rejected(): void
    {
        $category = CatalogCategory::factory()->create();
        $groups = $this->optionGroups();
        $second = $this->optionGroups()[0];
        $second['name'] = ' SIZE ';
        $groups[] = $second;
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category, ['options' => $groups]))->assertSessionHasErrors('options.1.name');
        $groups = $this->optionGroups();
        $groups[0]['choices'][0]['store_id'] = 999;
        $this->post(route('admin.catalog.store', $category->store), $this->data($category, ['options' => $groups]))->assertSessionHasErrors('options.0.choices.0');
        $groups = array_fill(0, 9, $this->optionGroups()[0]);
        $this->post(route('admin.catalog.store', $category->store), $this->data($category, ['options' => $groups]))->assertSessionHasErrors('options');
    }

    public function test_other_sellers_cannot_change_option_costs(): void
    {
        $category = CatalogCategory::factory()->create();
        $product = $category->store->catalogProducts()->create(['name' => 'Americano', 'price' => 17000, 'catalog_category_id' => $category->id, 'options' => $this->optionGroups()]);
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->patch(route('admin.catalog.update', [$category->store, $product]), $this->data($category, ['options' => []]))->assertNotFound();
        $this->get(route('admin.catalog.edit', [$category->store, $product]))->assertNotFound();
        $this->assertCount(1, $product->fresh()->options);
    }

    public function test_delete_removes_product_from_admin_and_public_catalog_but_preserves_record(): void
    {
        $category = CatalogCategory::factory()->create();
        $store = $category->store;
        $store->update(['is_listed' => true]);
        $product = $store->catalogProducts()->create(['name' => 'Americano', 'price' => 17000, 'is_visible' => true, 'catalog_category_id' => $category->id, 'options' => $this->optionGroups()]);
        $this->actingAs($store->owner)->delete(route('admin.catalog.destroy', [$store, $product]))->assertRedirectToRoute('admin.catalog.index', $store);
        $this->assertSoftDeleted($product);
        $this->assertSame(0, $store->catalogProducts()->count());
        $this->assertNotNull($product->fresh()->options);
        $this->get(route('admin.catalog.index', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->has('products.data', 0));
        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->has('products.data', 0));
        $this->get(route('admin.catalog.edit', [$store, $product]))->assertNotFound();
    }

    public function test_guest_other_owner_and_mismatched_parent_cannot_delete_a_product(): void
    {
        $category = CatalogCategory::factory()->create();
        $store = $category->store;
        $product = $store->catalogProducts()->create(['name' => 'Americano', 'price' => 17000, 'catalog_category_id' => $category->id]);
        $url = route('admin.catalog.destroy', [$store, $product]);
        $this->delete($url)->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create())->delete($url)->assertNotFound();
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->delete($url)->assertNotFound();
        $this->delete(route('admin.catalog.destroy', [$other, $product]))->assertNotFound();
        $this->assertNotSoftDeleted($product);
    }
}
