<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\Store;
use App\Services\ProductCostCalculator;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProductCostingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function costing(): array
    {
        return [
            'batch_yield' => 2, 'additional_cost' => 1000, 'target_margin' => '40',
            'ingredients' => [
                ['name' => 'Coffee beans', 'purchase_quantity' => 1, 'purchase_unit' => 'kg', 'purchase_cost' => 200000, 'used_quantity' => 36, 'used_unit' => 'g'],
                ['name' => 'Milk', 'purchase_quantity' => 1, 'purchase_unit' => 'l', 'purchase_cost' => 20000, 'used_quantity' => 200, 'used_unit' => 'ml'],
                ['name' => 'Cups', 'purchase_quantity' => 50, 'purchase_unit' => 'pcs', 'purchase_cost' => 50000, 'used_quantity' => 2, 'used_unit' => 'pcs'],
            ],
        ];
    }

    private function data(CatalogCategory $category, array $overrides = []): array
    {
        return array_replace(['name' => 'Latte', 'price' => 17000, 'catalog_category_id' => $category->id, 'costing' => $this->costing()], $overrides);
    }

    public function test_owner_can_save_private_ingredients_and_calculate_proportional_costs_and_margin_price(): void
    {
        $category = CatalogCategory::factory()->create();
        $store = $category->store;
        $this->actingAs($store->owner)->post(route('admin.catalog.store', $store), $this->data($category))->assertSessionHasNoErrors();
        $product = $store->catalogProducts()->sole();
        $this->assertTrue($product->is_visible);
        $this->assertArrayNotHasKey('costing', $product->toArray());
        $this->assertSame('Coffee beans', $product->costing['ingredients'][0]['name']);
        $this->assertSame(['priceLimitExceeded' => false, 'ingredientCosts' => [7200, 4000, 2000], 'batchCost' => 14200, 'unitCost' => 7100, 'suggestedPrice' => 11834], app(ProductCostCalculator::class)->calculate($product->costing));
        $this->get(route('admin.catalog.edit', [$store, $product]))->assertOk()->assertInertia(fn (Assert $page) => $page->where('product.costing.batch_yield', 2));
        $this->patch(route('admin.catalog.update', [$store, $product]), $this->data($category, ['costing' => null]))->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->costing);
    }

    public function test_costing_is_preserved_when_an_older_client_does_not_send_it(): void
    {
        $category = CatalogCategory::factory()->create();
        $product = $category->store->catalogProducts()->create(['name' => 'Latte', 'price' => 17000, 'catalog_category_id' => $category->id, 'costing' => $this->costing()]);
        $this->actingAs($category->store->owner)->patch(route('admin.catalog.update', [$category->store, $product]), ['name' => 'Latte', 'price' => 18000, 'catalog_category_id' => $category->id])->assertSessionHasNoErrors();
        $this->assertSame('Coffee beans', $product->fresh()->costing['ingredients'][0]['name']);
    }

    #[TestWith(['batch_yield', 0])]
    #[TestWith(['batch_yield', '1.5'])]
    #[TestWith(['target_margin', 100])]
    #[TestWith(['target_margin', -1])]
    #[TestWith(['additional_cost', -1])]
    #[TestWith(['ingredients', []])]
    public function test_invalid_batch_costing_does_not_create_a_product(string $field, mixed $value): void
    {
        $category = CatalogCategory::factory()->create();
        $costing = $this->costing();
        $costing[$field] = $value;
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category, ['costing' => $costing]))->assertSessionHasErrors('costing.'.$field);
        $this->assertDatabaseCount('rintis_catalog_products', 0);
    }

    #[TestWith(['purchase_quantity', 0])]
    #[TestWith(['used_quantity', '0.0001'])]
    #[TestWith(['purchase_quantity', '1e3'])]
    #[TestWith(['used_quantity', 1001])]
    #[TestWith(['purchase_cost', -1])]
    #[TestWith(['used_unit', 'bottle'])]
    public function test_invalid_ingredient_fields_do_not_create_a_product(string $field, mixed $value): void
    {
        $category = CatalogCategory::factory()->create();
        $costing = $this->costing();
        $costing['ingredients'][0][$field] = $value;
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category, ['costing' => $costing]))->assertSessionHasErrors('costing.ingredients.0.'.$field);
        $this->assertDatabaseCount('rintis_catalog_products', 0);
    }

    public function test_mismatched_units_and_excessive_estimates_are_rejected_without_partial_save(): void
    {
        $category = CatalogCategory::factory()->create();
        $costing = $this->costing();
        $costing['ingredients'][0]['used_unit'] = 'ml';
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category, ['costing' => $costing]))->assertSessionHasErrors('costing');
        $costing = $this->costing();
        $costing['ingredients'][0] = array_replace($costing['ingredients'][0], ['purchase_quantity' => '0.001', 'purchase_unit' => 'g', 'purchase_cost' => 999999999, 'used_quantity' => 1000, 'used_unit' => 'kg']);
        $this->post(route('admin.catalog.store', $category->store), $this->data($category, ['costing' => $costing]))->assertSessionHasErrors('costing');
        $this->assertDatabaseCount('rintis_catalog_products', 0);
    }

    public function test_guest_and_another_seller_cannot_read_or_modify_private_costing(): void
    {
        $category = CatalogCategory::factory()->create();
        $store = $category->store;
        $store->update(['is_listed' => true]);
        $product = $store->catalogProducts()->create(['name' => 'Latte', 'price' => 17000, 'is_visible' => true, 'catalog_category_id' => $category->id, 'costing' => $this->costing()]);
        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->missing('products.data.0.costing')->missing('products.data.0.price')->missing('products.data.0.ingredients'));
        $this->get(route('admin.catalog.edit', [$store, $product]))->assertRedirectToRoute('login');
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->get(route('admin.catalog.edit', [$store, $product]))->assertNotFound();
        $this->patch(route('admin.catalog.update', [$store, $product]), $this->data($category, ['costing' => null]))->assertNotFound();
        $this->get(route('admin.catalog.edit', [$other, $product]))->assertNotFound();
        $this->assertSame('Coffee beans', $product->fresh()->costing['ingredients'][0]['name']);
    }

    public function test_zero_cost_optional_margin_and_rounding_are_supported(): void
    {
        $costing = $this->costing();
        $costing['ingredients'] = [['name' => 'Item', 'purchase_quantity' => 2, 'purchase_unit' => 'pcs', 'purchase_cost' => 1, 'used_quantity' => 1, 'used_unit' => 'pcs']];
        $costing['additional_cost'] = 0;
        $costing['batch_yield'] = 1;
        $costing['target_margin'] = null;
        $result = app(ProductCostCalculator::class)->calculate($costing);
        $this->assertSame(1, $result['unitCost']);
        $this->assertNull($result['suggestedPrice']);
        $costing['ingredients'][0]['purchase_cost'] = 0;
        $costing['target_margin'] = '0';
        $this->assertSame(0, app(ProductCostCalculator::class)->calculate($costing)['suggestedPrice']);
    }

    public function test_excessive_items_and_untrusted_computed_values_are_rejected(): void
    {
        $category = CatalogCategory::factory()->create();
        $costing = $this->costing();
        $costing['ingredients'] = array_fill(0, 51, $costing['ingredients'][0]);
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category, ['costing' => $costing]))->assertSessionHasErrors('costing.ingredients');
        $costing = $this->costing();
        $costing['unitCost'] = 1;
        $this->post(route('admin.catalog.store', $category->store), $this->data($category, ['costing' => $costing]))->assertSessionHasErrors('costing');
        $this->assertDatabaseCount('rintis_catalog_products', 0);
    }

    public function test_profit_target_is_added_to_hpp_and_does_not_change_base_price_automatically(): void
    {
        $category = CatalogCategory::factory()->create();
        $costing = ['batch_yield' => 1, 'additional_cost' => 0, 'target_markup' => 100, 'ingredients' => [
            ['name' => 'Coffee', 'purchase_quantity' => 1, 'purchase_unit' => 'pcs', 'purchase_cost' => 8000, 'used_quantity' => 1, 'used_unit' => 'pcs'],
        ]];
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category, ['costing' => $costing]))->assertSessionHasNoErrors();
        $product = $category->store->catalogProducts()->sole();
        $calculator = app(ProductCostCalculator::class);
        $this->assertSame(16000, $calculator->calculate($product->costing)['suggestedPrice']);
        $this->assertSame(17000, $product->price);
        foreach ([0 => 8000, 50 => 12000, 200 => 24000] as $rate => $expected) {
            $costing['target_markup'] = $rate;
            $this->assertSame($expected, $calculator->calculate($costing)['suggestedPrice']);
        }
        $costing['target_markup'] = null;
        $this->assertNull($calculator->calculate($costing)['suggestedPrice']);
        foreach ([-1, 1000001, '1.001'] as $invalid) {
            $costing['target_markup'] = $invalid;
            $this->patch(route('admin.catalog.update', [$category->store, $product]), $this->data($category, ['costing' => $costing]))->assertSessionHasErrors('costing.target_markup');
        }
        $costing['target_markup'] = 1000000;
        $costing['ingredients'][0]['purchase_cost'] = 999999999;
        $this->assertTrue($calculator->calculate($costing)['priceLimitExceeded']);
        $this->assertNull($calculator->calculate($costing)['suggestedPrice']);
    }
}
