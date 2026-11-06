<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\User;
use App\Services\SaveOpenOrder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class OpenOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function storeReady(): Store
    {
        return Store::factory()->create(['pickup_enabled' => true, 'latitude' => 0.9, 'longitude' => 109, 'is_listed' => true]);
    }

    private function data(Store $store, array $overrides = []): array
    {
        $category = $store->catalogCategories()->firstOrCreate(['name' => 'Coffee']);
        $product = $store->catalogProducts()->firstOrCreate(['name' => 'Americano'], ['price' => 17000, 'catalog_category_id' => $category->id, 'is_visible' => true]);

        return array_replace(['name' => 'Weekend coffee', 'intent' => 'publish', 'opens_at' => now()->setTimezone('Asia/Jakarta')->subHour()->format('Y-m-d\TH:i'),
            'closes_at' => now()->setTimezone('Asia/Jakarta')->addDay()->format('Y-m-d\TH:i'), 'pickup_enabled' => true, 'delivery_enabled' => false,
            'items' => [['catalog_product_id' => $product->id, 'price' => 18000, 'quota' => 20]]], $overrides);
    }

    public function test_new_sessions_inherit_defaults_and_overrides_do_not_change_store_or_old_sessions(): void
    {
        $store = $this->storeReady();
        $store->update(['delivery_options' => ['priority' => ['enabled' => true, 'fee' => 5000, 'minutes' => 30], 'hemat' => ['enabled' => true, 'fee' => 1500, 'minutes' => 60]], 'delivery_fee_mode' => 'fixed', 'delivery_fixed_fee' => 3000]);
        $first = app(SaveOpenOrder::class)->handle($store, $this->data($store, ['intent' => 'draft', 'delivery_enabled' => true]));
        $this->assertSame(5000, $first->fulfillment['delivery_options']['priority']['fee']);
        $this->assertSame(3000, $first->fulfillment['delivery_fixed_fee']);
        $second = app(SaveOpenOrder::class)->handle($store, $this->data($store, ['intent' => 'draft', 'delivery_enabled' => true, 'priority_fee' => 7000, 'delivery_fixed_fee' => 4000]));
        $this->assertSame(7000, $second->fulfillment['delivery_options']['priority']['fee']);
        $this->assertSame(4000, $second->fulfillment['delivery_fixed_fee']);
        $this->assertSame(5000, $store->fresh()->delivery_options['priority']['fee']);
        $store->update(['delivery_options' => ['priority' => ['enabled' => false, 'fee' => 100, 'minutes' => 20]]]);
        $this->assertSame(5000, $first->fresh()->fulfillment['delivery_options']['priority']['fee']);
    }

    public function test_owner_can_create_and_publish_a_session_with_private_snapshots(): void
    {
        $store = $this->storeReady();
        $data = $this->data($store);
        $product = $store->catalogProducts()->sole();
        $product->update(['costing' => ['private_recipe' => 'secret']]);
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasNoErrors()->assertRedirectToRoute('admin.open-orders.index', $store);
        $period = $store->openOrders()->sole();
        $this->assertSame('open', $period->displayStatus());
        $this->get(route('admin.open-orders.orders', [$store, $period]))->assertOk()->assertInertia(fn (Assert $page) => $page->where('period.closesAt', $period->closes_at->toIso8601String())->where('paymentDeadline', $period->closes_at->subMinutes(15)->toIso8601String())->has('serverNow')->missing('period.fulfillment'));
        $this->assertSame($store->address_line, $period->fulfillment['address_line']);
        $this->assertSame(18000, $period->items()->sole()->price);
        $product->update(['name' => 'Changed', 'price' => 99000, 'costing' => null]);
        $store->update(['address_line' => 'Changed private address', 'pickup_instructions' => 'Changed']);
        $this->assertSame('Americano', $period->items()->sole()->name);
        $this->assertSame('secret', $period->items()->sole()->costing['private_recipe']);
        $this->assertNotSame('Changed private address', $period->fresh()->fulfillment['address_line']);
        $this->post(route('logout'));
        $this->get(route('open-order', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('period.status', 'open')->where('items.0.price', 18000)->where('items.0.quota', 20)
            ->missing('items.0.costing')->missing('period.fulfillment')->missing('store.address_line')->missing('store.latitude'));
    }

    public function test_draft_is_not_public_can_be_edited_and_then_scheduled(): void
    {
        $store = $this->storeReady();
        $data = $this->data($store, ['intent' => 'draft', 'pickup_enabled' => false, 'items' => []]);
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasNoErrors();
        $period = $store->openOrders()->sole();
        $this->get(route('open-order', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('period', null)->has('items', 0));
        $this->get(route('admin.open-orders.edit', [$store, $period]))->assertOk();
        $data = $this->data($store, ['opens_at' => now()->setTimezone('Asia/Jakarta')->addHour()->format('Y-m-d\TH:i')]);
        $this->patch(route('admin.open-orders.update', [$store, $period]), $data)->assertSessionHasNoErrors();
        $this->assertSame('scheduled', $period->fresh()->displayStatus());
        $this->assertSame($data['opens_at'], $period->fresh()->opens_at->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i'));
        $this->assertSame('UTC', $period->fresh()->opens_at->timezoneName);
    }

    public function test_only_one_open_or_scheduled_session_is_allowed_and_draft_is_not_partially_published(): void
    {
        $store = $this->storeReady();
        $data = $this->data($store);
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasNoErrors();
        $this->post(route('admin.open-orders.store', $store), [...$data, 'intent' => 'draft', 'name' => 'Next'])->assertSessionHasNoErrors();
        $draft = $store->openOrders()->where('status', 'draft')->sole();
        $this->patch(route('admin.open-orders.update', [$store, $draft]), $data)->assertSessionHasErrors('name');
        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame(1, $store->openOrders()->where('status', 'published')->count());
        $this->assertSame('Next', $draft->fresh()->name);
    }

    public function test_close_and_cancel_are_owner_only_and_allow_a_new_session(): void
    {
        $store = $this->storeReady();
        $data = $this->data($store);
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasNoErrors();
        $period = $store->openOrders()->sole();
        $this->patch(route('admin.open-orders.close', [$store, $period]))->assertSessionHasNoErrors();
        $this->assertSame('closed', $period->fresh()->status);
        $this->get(route('open-order', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('period.status', 'closed')->has('items', 0));
        $this->post(route('admin.open-orders.store', $store), [...$data, 'name' => 'Second'])->assertSessionHasNoErrors();
        $next = $store->openOrders()->latest('id')->firstOrFail();
        $this->patch(route('admin.open-orders.cancel', [$store, $next]))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $next->fresh()->status);
        $this->assertDatabaseCount('rintis_open_order_items', 2);
    }

    #[TestWith(['items', []])]
    #[TestWith(['closes_at', '2000-01-01T12:00'])]
    public function test_invalid_publication_does_not_save_a_session(string $field, mixed $value): void
    {
        $store = $this->storeReady();
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $this->data($store, [$field => $value]))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('rintis_open_orders', 0);
    }

    public function test_publication_requires_valid_methods_location_and_delivery_config(): void
    {
        $store = $this->storeReady();
        $data = $this->data($store, ['pickup_enabled' => false, 'delivery_starts_at' => now()->setTimezone('Asia/Jakarta')->addDay()->addMinutes(30)->format('Y-m-d\TH:i'), 'delivery_ends_at' => now()->setTimezone('Asia/Jakarta')->addDay()->addHours(2)->format('Y-m-d\TH:i')]);
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasErrors('pickup_enabled');
        $store->update(['latitude' => null]);
        $this->post(route('admin.open-orders.store', $store), [...$data, 'pickup_enabled' => true])->assertSessionHasErrors('pickup_enabled');
        $store->update(['latitude' => 0.9, 'delivery_enabled' => true]);
        $this->post(route('admin.open-orders.store', $store), [...$data, 'delivery_enabled' => true])->assertSessionHasErrors('delivery_enabled');
        $store->update(['delivery_fee_mode' => 'fixed', 'delivery_fixed_fee' => 0, 'delivery_min_distance_m' => 100, 'delivery_max_distance_m' => 5000]);
        $this->post(route('admin.open-orders.store', $store), [...$data, 'delivery_enabled' => true])->assertSessionHasNoErrors();
        $this->assertSame(0, $store->openOrders()->sole()->fulfillment['delivery_fixed_fee']);
    }

    public function test_delivery_window_is_required_before_publication_and_visible_before_checkout(): void
    {
        $store = $this->storeReady();
        $store->update(['delivery_enabled' => true, 'delivery_fee_mode' => 'fixed', 'delivery_fixed_fee' => 0, 'delivery_min_distance_m' => 100, 'delivery_max_distance_m' => 5000]);
        $data = $this->data($store, ['delivery_enabled' => true]);
        $url = route('admin.open-orders.store', $store);
        $this->actingAs($store->owner)->post($url, $data)->assertSessionHasErrors(['delivery_starts_at', 'delivery_ends_at']);
        $starts = now()->setTimezone('Asia/Jakarta')->addDay()->addMinutes(30)->format('Y-m-d\TH:i');
        $ends = now()->setTimezone('Asia/Jakarta')->addDay()->addHours(2)->format('Y-m-d\TH:i');
        $this->post($url, [...$data, 'delivery_starts_at' => $data['opens_at'], 'delivery_ends_at' => $ends])->assertSessionHasErrors('delivery_starts_at');
        $this->post($url, [...$data, 'delivery_starts_at' => $starts, 'delivery_ends_at' => $ends, 'priority_enabled' => true, 'priority_fee' => -1, 'priority_minutes' => 0, 'hemat_enabled' => true])->assertSessionHasErrors(['priority_fee', 'priority_minutes', 'hemat_fee', 'hemat_minutes']);
        $this->post($url, [...$data, 'delivery_starts_at' => $starts, 'delivery_ends_at' => $ends, 'priority_enabled' => true, 'priority_fee' => 5000, 'priority_minutes' => 30, 'hemat_enabled' => true, 'hemat_fee' => 1500, 'hemat_minutes' => 60])->assertSessionHasNoErrors();
        $period = $store->openOrders()->sole();
        $this->get(route('checkout.show', [$store, $period]))->assertInertia(fn (Assert $page) => $page->where('period.deliveryWindow.startsAt', $period->fulfillment['delivery_starts_at'])->where('period.deliveryWindow.endsAt', $period->fulfillment['delivery_ends_at']));
    }

    public function test_foreign_deleted_products_invalid_quota_and_duplicate_selection_are_rejected(): void
    {
        $store = $this->storeReady();
        $data = $this->data($store);
        $other = $this->storeReady();
        $foreign = $this->data($other)['items'][0];
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), [...$data, 'items' => [$foreign]])->assertSessionHasErrors('items.0.catalog_product_id');
        $item = $data['items'][0];
        $this->post(route('admin.open-orders.store', $store), [...$data, 'items' => [[...$item, 'quota' => 0]]])->assertSessionHasErrors('items.0.quota');
        $this->post(route('admin.open-orders.store', $store), [...$data, 'items' => [$item, $item]])->assertSessionHasErrors('items.0.catalog_product_id');
        $store->catalogProducts()->sole()->delete();
        $this->post(route('admin.open-orders.store', $store), $data)->assertSessionHasErrors('items.0.catalog_product_id');
        $this->assertDatabaseCount('rintis_open_orders', 0);
    }

    public function test_guest_and_other_seller_cannot_manage_sessions_or_use_mismatched_parent(): void
    {
        $store = $this->storeReady();
        $data = $this->data($store);
        $this->get(route('admin.open-orders.index', $store))->assertRedirectToRoute('login');
        $this->post(route('admin.open-orders.store', $store), $data)->assertRedirectToRoute('login');
        $this->actingAs(User::factory()->create())->get(route('admin.open-orders.index', $store))->assertNotFound();
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasNoErrors();
        $period = $store->openOrders()->sole();
        $other = $this->storeReady();
        $this->actingAs($other->owner)->get(route('admin.open-orders.index', $store))->assertNotFound();
        $this->patch(route('admin.open-orders.close', [$store, $period]))->assertNotFound();
        $this->get(route('admin.open-orders.edit', [$other, $period]))->assertNotFound();
        $this->patch(route('admin.open-orders.cancel', [$other, $period]))->assertNotFound();
        $this->assertSame('published', $period->fresh()->status);
    }

    public function test_publication_is_time_based_and_old_session_does_not_block_next_one(): void
    {
        $this->travelTo(now()->startOfMinute());
        $store = $this->storeReady();
        $data = $this->data($store, ['closes_at' => now()->setTimezone('Asia/Jakarta')->addHour()->format('Y-m-d\TH:i'), 'items' => []]);
        $data['items'] = $this->data($store)['items'];
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasNoErrors();
        $period = $store->openOrders()->sole();
        $this->travel(2)->hours();
        $this->assertSame('ended', $period->fresh()->displayStatus());
        $this->get(route('open-order', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->has('items', 0));
        $this->post(route('admin.open-orders.store', $store), $this->data($store))->assertSessionHasNoErrors();
        $this->assertSame(2, $store->openOrders()->count());
    }

    public function test_published_snapshot_cannot_be_rewritten_and_unlimited_quota_is_preserved(): void
    {
        $store = $this->storeReady();
        $data = $this->data($store);
        $data['items'][0]['quota'] = null;
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasNoErrors();
        $period = $store->openOrders()->sole();
        $this->patch(route('admin.open-orders.update', [$store, $period]), [...$data, 'name' => 'Changed'])->assertSessionHasErrors('name');
        $this->assertSame('Weekend coffee', $period->fresh()->name);
        $this->assertNull($period->items()->sole()->quota);
    }

    public function test_public_option_price_snapshot_does_not_expose_option_hpp_or_live_product_changes(): void
    {
        $store = $this->storeReady();
        $data = $this->data($store);
        $product = $store->catalogProducts()->sole();
        $product->update(['options' => [['id' => 'size', 'name' => 'Size', 'required' => true, 'selection' => 'single', 'choices' => [
            ['id' => 'large', 'name' => 'Large', 'price_adjustment' => 3000, 'cost_adjustment' => 1000],
        ]]]]);
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasNoErrors();
        $product->update(['options' => []]);
        $this->get(route('open-order', $store))->assertOk()->assertInertia(fn (Assert $page) => $page
            ->where('items.0.options.0.choices.0.price_adjustment', 3000)->missing('items.0.options.0.choices.0.cost_adjustment'));
    }
}
