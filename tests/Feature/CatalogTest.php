<?php

namespace Tests\Feature;

use App\Models\CatalogCategory;
use App\Models\CatalogProduct;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function data(CatalogCategory $category, array $overrides = []): array
    {
        return array_replace([
            'name' => 'Iced Latte', 'description' => 'Coffee and milk.', 'price' => 18000,
            'catalog_category_id' => $category->id, 'is_visible' => false,
        ], $overrides);
    }

    public function test_owner_can_add_and_rename_product_categories_scoped_to_their_store(): void
    {
        $store = Store::factory()->create();
        $this->actingAs($store->owner)->post(route('admin.catalog.categories.store', $store), ['name' => 'Coffee', 'store_id' => 999])
            ->assertRedirectToRoute('admin.catalog.index', [$store, 'tab' => 'categories']);
        $category = $store->catalogCategories()->sole();
        $this->assertSame('Coffee', $category->name);
        $this->patch(route('admin.catalog.categories.update', [$store, $category]), ['name' => 'Coffee & Tea'])->assertRedirect();
        $this->assertSame('Coffee & Tea', $category->fresh()->name);
    }

    public function test_category_names_are_unique_within_store_but_can_be_reused_by_other_stores(): void
    {
        $category = CatalogCategory::factory()->create(['name' => 'Coffee']);
        $store = $category->store;
        $this->actingAs($store->owner)->post(route('admin.catalog.categories.store', $store), ['name' => 'Coffee'])->assertSessionHasErrors('name');
        $this->post(route('admin.catalog.categories.store', $store), ['name' => ''])->assertSessionHasErrors('name');
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->post(route('admin.catalog.categories.store', $other), ['name' => 'Coffee'])->assertSessionHasNoErrors();
        $this->assertDatabaseCount('rintis_catalog_categories', 2);
    }

    public function test_owner_can_manage_products_with_no_photo_and_valid_zero_price(): void
    {
        $category = CatalogCategory::factory()->create();
        $store = $category->store;
        $this->actingAs($store->owner)->post(route('admin.catalog.store', $store), $this->data($category, ['price' => 0, 'store_id' => 999, 'image_path' => 'forged.php']))->assertRedirectToRoute('admin.catalog.index', $store);
        $product = $store->catalogProducts()->sole();
        $this->assertSame(0, $product->price);
        $this->assertNull($product->image_path);
        $this->assertTrue($product->is_visible);
        $this->get(route('admin.catalog.edit', [$store, $product]))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/ProductForm')->where('product.name', 'Iced Latte'));
        $this->patch(route('admin.catalog.update', [$store, $product]), $this->data($category, ['name' => 'Hot Latte', 'is_visible' => true]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('rintis_catalog_products', ['id' => $product->id, 'name' => 'Hot Latte', 'price' => 18000, 'is_visible' => true]);
        $this->patch(route('admin.catalog.update', [$store, $product]), $this->data($category))->assertRedirect();
        $this->assertDatabaseHas('rintis_catalog_products', ['id' => $product->id, 'is_visible' => true]);
    }

    #[TestWith(['name', ''])]
    #[TestWith(['name', 151])]
    #[TestWith(['description', 2001])]
    #[TestWith(['price', -1])]
    #[TestWith(['price', '10.5'])]
    #[TestWith(['price', 1000000000])]
    #[TestWith(['catalog_category_id', 999999])]
    public function test_invalid_product_fields_are_not_saved(string $field, mixed $value): void
    {
        $category = CatalogCategory::factory()->create();
        if (in_array($field, ['name', 'description'], true) && is_int($value)) {
            $value = str_repeat('a', $value);
        }
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category, [$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('rintis_catalog_products', 0);
    }

    public function test_category_from_another_store_cannot_be_used_for_product_creation_or_update(): void
    {
        $category = CatalogCategory::factory()->create();
        $product = CatalogProduct::factory()->create(['catalog_category_id' => $category->id]);
        $otherCategory = CatalogCategory::factory()->create();
        $store = $category->store;
        $this->actingAs($store->owner);

        $this->post(route('admin.catalog.store', $store), $this->data($otherCategory))->assertSessionHasErrors('catalog_category_id');
        $this->patch(route('admin.catalog.update', [$store, $product]), $this->data($otherCategory))->assertSessionHasErrors('catalog_category_id');
        $this->assertDatabaseHas('rintis_catalog_products', ['id' => $product->id, 'catalog_category_id' => $category->id, 'store_id' => $store->id]);
        $this->assertDatabaseCount('rintis_catalog_products', 1);
    }

    public function test_guests_and_other_users_cannot_manage_catalog(): void
    {
        $category = CatalogCategory::factory()->create();
        $store = $category->store;
        $this->get(route('admin.catalog.index', $store))->assertRedirectToRoute('login');
        $this->post(route('admin.catalog.store', $store), $this->data($category))->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create());
        $this->get(route('admin.catalog.index', $store))->assertNotFound();
        $this->post(route('admin.catalog.store', $store), $this->data($category))->assertNotFound();
        $this->assertDatabaseCount('rintis_catalog_products', 0);
    }

    public function test_another_owner_cannot_read_or_modify_catalog_even_with_tampered_nested_ids(): void
    {
        $product = CatalogProduct::factory()->create();
        $store = $product->store;
        $category = $product->catalogCategory;
        $other = Store::factory()->create();
        $this->actingAs($other->owner);

        $this->get(route('admin.catalog.index', $store))->assertNotFound();
        $this->get(route('admin.catalog.create', $store))->assertNotFound();
        $this->get(route('admin.catalog.edit', [$store, $product]))->assertNotFound();
        $this->patch(route('admin.catalog.update', [$store, $product]), $this->data($category))->assertNotFound();
        $this->get(route('admin.catalog.edit', [$other, $product]))->assertNotFound();
        $this->patch(route('admin.catalog.update', [$other, $product]), $this->data($category))->assertNotFound();
        $this->post(route('admin.catalog.categories.store', $store), ['name' => 'Stolen'])->assertNotFound();
        $this->patch(route('admin.catalog.categories.update', [$other, $category]), ['name' => 'Stolen'])->assertNotFound();
        $this->assertDatabaseHas('rintis_catalog_products', ['id' => $product->id, 'name' => $product->name, 'store_id' => $store->id]);
        $this->assertDatabaseHas('rintis_catalog_categories', ['id' => $category->id, 'name' => $category->name]);
    }

    public function test_public_catalog_contains_only_visible_products_from_selected_store_without_prices(): void
    {
        $store = Store::factory()->create(['is_listed' => true]);
        $category = CatalogCategory::factory()->create(['store_id' => $store->id]);
        $shown = CatalogProduct::factory()->create(['catalog_category_id' => $category->id, 'name' => 'Public Coffee', 'is_visible' => true]);
        CatalogProduct::factory()->create(['catalog_category_id' => $category->id, 'name' => 'Hidden Coffee']);
        CatalogProduct::factory()->create(['is_visible' => true]);

        $this->get(route('storefront', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->has('products.data', 1)->where('products.data.0.name', $shown->name)
            ->where('products.data.0.category', $category->name)->missing('products.data.0.price')
            ->missing('products.data.0.store_id')->missing('products.data.0.image_path'));
    }

    public function test_admin_search_is_scoped_and_hidden_products_remain_manageable(): void
    {
        $product = CatalogProduct::factory()->create(['name' => 'Find Coffee']);
        CatalogProduct::factory()->create(['name' => 'Find Other']);

        $this->actingAs($product->store->owner)->get(route('admin.catalog.index', [$product->store, 'search' => 'find']))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Catalog')->has('products.data', 1)
                ->where('products.data.0.id', $product->id)->where('products.data.0.is_visible', false)->where('products.data.0.price', 18000));
    }

    public function test_unlisted_store_catalog_is_available_only_to_its_owner_preview(): void
    {
        $product = CatalogProduct::factory()->create(['is_visible' => true]);
        $this->get(route('storefront', $product->store))->assertNotFound();
        $this->actingAs($product->store->owner)->get(route('storefront', $product->store))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('isPreview', true)->has('products.data', 1));
    }

    public function test_product_photo_can_be_uploaded_replaced_and_removed_without_orphaning_old_file(): void
    {
        Storage::fake('public');
        $category = CatalogCategory::factory()->create();
        $store = $category->store;
        $this->actingAs($store->owner)->post(route('admin.catalog.store', $store), $this->data($category, ['photo' => UploadedFile::fake()->image('coffee.jpg')]))->assertSessionHasNoErrors();
        $product = $store->catalogProducts()->sole();
        $oldPath = $product->image_path;
        Storage::disk('public')->assertExists($oldPath);
        $this->patch(route('admin.catalog.update', [$store, $product]), $this->data($category, ['photo' => UploadedFile::fake()->image('new.png')]))->assertSessionHasNoErrors();
        $newPath = $product->fresh()->image_path;
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
        $this->patch(route('admin.catalog.update', [$store, $product]), $this->data($category, ['remove_image' => true]))->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->image_path);
        Storage::disk('public')->assertMissing($newPath);
    }

    public function test_invalid_photo_type_size_dimensions_and_conflicting_removal_are_rejected(): void
    {
        Storage::fake('public');
        $category = CatalogCategory::factory()->create();
        $this->actingAs($category->store->owner);
        foreach ([UploadedFile::fake()->create('script.php', 1, 'application/x-php'), UploadedFile::fake()->image('big.jpg')->size(5121), UploadedFile::fake()->image('wide.png', 4097, 1)] as $photo) {
            $this->post(route('admin.catalog.store', $category->store), $this->data($category, ['photo' => $photo]))->assertSessionHasErrors('photo');
        }
        $this->post(route('admin.catalog.store', $category->store), $this->data($category, ['photo' => UploadedFile::fake()->image('coffee.jpg'), 'remove_image' => true]))->assertSessionHasErrors('photo');
        $this->assertDatabaseCount('rintis_catalog_products', 0);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_client_cannot_hide_created_products(): void
    {
        $category = CatalogCategory::factory()->create();
        $this->actingAs($category->store->owner)->post(route('admin.catalog.store', $category->store), $this->data($category, ['is_visible' => 'invalid']))->assertSessionHasNoErrors();
        $this->assertTrue($category->store->catalogProducts()->sole()->is_visible);
    }
}
