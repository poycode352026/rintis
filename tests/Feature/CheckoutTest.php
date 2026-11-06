<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\OpenOrder;
use App\Models\Order;
use App\Models\Store;
use App\Services\PlaceOrder;
use App\Services\SaveOpenOrder;
use App\Services\UpdateOrderStatus;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.ssr.enabled' => false, 'rintis.routing_demo' => false, 'rintis.routing_url' => null]);
    }

    private function period(): OpenOrder
    {
        $store = Store::factory()->create(['pickup_enabled' => true, 'delivery_enabled' => true, 'latitude' => 0.9, 'longitude' => 109, 'is_listed' => true,
            'delivery_fee_mode' => 'road_distance', 'delivery_rate_per_km' => 2000, 'delivery_min_distance_m' => 100, 'delivery_max_distance_m' => 5000]);
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $product = $store->catalogProducts()->create(['name' => 'Americano', 'price' => 17000, 'catalog_category_id' => $category->id, 'image_path' => 'coffee.jpg', 'costing' => ['secret' => 123], 'options' => [
            ['id' => (string) Str::uuid(), 'name' => 'Size', 'required' => true, 'selection' => 'single', 'choices' => [
                ['id' => (string) Str::uuid(), 'name' => 'Large', 'price_adjustment' => 3000, 'cost_adjustment' => 1000],
                ['id' => (string) Str::uuid(), 'name' => 'Small', 'price_adjustment' => 0, 'cost_adjustment' => 0],
            ]],
        ]]);
        app(SaveOpenOrder::class)->handle($store, ['name' => 'Weekend', 'intent' => 'publish', 'opens_at' => now()->setTimezone('Asia/Jakarta')->subHour()->format('Y-m-d\TH:i'),
            'closes_at' => now()->setTimezone('Asia/Jakarta')->addDay()->format('Y-m-d\TH:i'), 'pickup_enabled' => true, 'delivery_enabled' => true,
            'items' => [['catalog_product_id' => $product->id, 'price' => 18000, 'quota' => 3]]]);

        return $store->openOrders()->sole();
    }

    private function data(OpenOrder $period, array $overrides = []): array
    {
        $item = $period->items()->sole();

        return array_replace(['idempotency_key' => (string) Str::uuid(), 'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'pickup', 'payment_method' => 'cash',
            'items' => [['id' => $item->id, 'quantity' => 2, 'choices' => [$item->options[0]['choices'][0]['id']]]]], $overrides);
    }

    #[TestWith(['standard', 2400])]
    #[TestWith(['priority', 7400])]
    #[TestWith(['hemat', 1500])]
    public function test_delivery_choice_uses_seller_prices_and_snapshots_time(string $service, int $expectedFee): void
    {
        $this->freezeTime();
        Http::preventStrayRequests();
        config(['rintis.routing_url' => 'https://routing.test/route/v1/driving']);
        Http::fake(['routing.test/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 1200]]])]);
        $period = $this->period();
        $period->update(['fulfillment' => [...$period->fulfillment, 'delivery_options' => ['priority' => ['enabled' => true, 'fee' => 5000, 'minutes' => 30], 'hemat' => ['enabled' => true, 'fee' => 1500, 'minutes' => 60]]]]);
        $store = $period->store;
        $this->get(route('checkout.show', [$store, $period]))->assertInertia(fn (Assert $page) => $page->has('period.deliveryOptions', 3)->where('period.deliveryOptions.2.fee', 1500));
        $quote = $this->postJson(route('checkout.quote', [$store, $period]), ['latitude' => 0.91, 'longitude' => 109.01])->assertOk()->json();
        $this->post(route('checkout.store', [$store, $period]), $this->data($period, ['method' => 'delivery', 'delivery_service' => $service, 'delivery_address' => 'Delivery entrance', 'latitude' => 0.91, 'longitude' => 109.01, 'quote_id' => $quote['id'], 'delivery_fee' => 1]))->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame($expectedFee, $order->delivery_fee);
        $this->assertSame(42000 + $expectedFee, $order->total);
        $this->assertSame($service, $order->fulfillment['delivery_service']);
        if ($service === 'hemat') {
            $this->assertSame($period->closes_at->addMinutes(15)->toIso8601String(), $order->fulfillment['delivery_starts_at']);
            $order->forceFill(['status' => 'ready'])->save();
            try {
                app(UpdateOrderStatus::class)->handle($order, ['expected_status' => 'ready', 'status' => 'out_for_delivery'], $store->owner);
                $this->fail('Hemat must wait for its scheduled start.');
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('status', $exception->errors());
            }
            $this->travelTo($period->closes_at->addMinutes(15));
            app(UpdateOrderStatus::class)->handle($order, ['expected_status' => 'ready', 'status' => 'out_for_delivery'], $store->owner);
            $this->assertSame('out_for_delivery', $order->fresh()->status);
        }
        if ($service === 'priority') {
            $this->assertSame(now()->addMinutes(30)->toIso8601String(), $order->fulfillment['delivery_ends_at']);
        }
        $snapshot = $order->fulfillment;
        $period->update(['fulfillment' => []]);
        $this->assertSame($snapshot, $order->fresh()->fulfillment);
    }

    public function test_disabled_delivery_choice_is_rejected_without_creating_an_order(): void
    {
        Http::preventStrayRequests();
        config(['rintis.routing_url' => 'https://routing.test/route/v1/driving']);
        Http::fake(['routing.test/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 1200]]])]);
        $period = $this->period();
        $quote = $this->postJson(route('checkout.quote', [$period->store, $period]), ['latitude' => 0.91, 'longitude' => 109.01])->assertOk()->json();
        $this->post(route('checkout.store', [$period->store, $period]), $this->data($period, ['method' => 'delivery', 'delivery_service' => 'priority', 'delivery_address' => 'Delivery entrance', 'latitude' => 0.91, 'longitude' => 109.01, 'quote_id' => $quote['id']]))->assertSessionHasErrors('delivery_service');
        $this->assertDatabaseCount('rintis_orders', 0);
        $this->assertSame(0, $period->items()->sole()->ordered_quantity);
    }

    public function test_delivery_requires_a_written_address_even_when_coordinates_are_present(): void
    {
        $period = $this->period();
        $this->post(route('checkout.store', [$period->store, $period]), $this->data($period, ['method' => 'delivery', 'delivery_address' => '   ', 'latitude' => 0.9, 'longitude' => 109]))->assertSessionHasErrors('delivery_address');
        $this->assertDatabaseCount('rintis_orders', 0);
    }

    public function test_seller_cancels_unpaid_cash_once_and_releases_quota(): void
    {
        $period = $this->period();
        $this->post(route('checkout.store', [$period->store, $period]), $this->data($period))->assertSessionHasNoErrors();
        $order = Order::sole();
        $url = route('admin.open-orders.order-cancel', [$period->store, $period, $order]);
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->post($url, ['reason' => 'Changed plans'])->assertNotFound();
        $this->actingAs($period->store->owner)->post($url, ['reason' => ''])->assertSessionHasErrors('reason');
        $this->post($url, ['reason' => 'Customer cancelled'])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(0, $period->items()->sole()->ordered_quantity);
        $this->post($url, ['reason' => 'Repeated request'])->assertSessionHasNoErrors();
        $this->assertCount(1, collect($order->fresh()->fulfillment_history)->where('status', 'cancelled'));
        $this->assertSame(42000, $order->fresh()->total);
    }

    public function test_seller_cannot_cancel_paid_or_dispatched_cash(): void
    {
        $period = $this->period();
        $this->post(route('checkout.store', [$period->store, $period]), $this->data($period))->assertSessionHasNoErrors();
        $order = Order::sole();
        $url = route('admin.open-orders.order-cancel', [$period->store, $period, $order]);
        $this->actingAs($period->store->owner);
        $order->update(['payment_status' => 'paid']);
        $this->post($url, ['reason' => 'Cancel'])->assertSessionHasErrors('cancel');
        $order->forceFill(['payment_status' => 'unpaid', 'status' => 'out_for_delivery'])->save();
        $this->post($url, ['reason' => 'Cancel'])->assertSessionHasErrors('cancel');
        $this->assertSame('out_for_delivery', $order->fresh()->status);
        $this->assertSame(2, $period->items()->sole()->ordered_quantity);
    }

    public function test_guest_selects_photo_menu_and_places_cash_order_with_private_receipt(): void
    {
        $period = $this->period();
        $store = $period->store;
        $this->get(route('open-order', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('items.0.remaining', 3)->where('items.0.imageUrl', fn ($url) => str_ends_with($url, '/coffee.jpg'))->missing('items.0.costing'));
        $this->get(route('checkout.show', [$store, $period]))->assertOk()->assertInertia(fn (Assert $page) => $page->missing('store.address_line')->missing('store.latitude')->missing('items.0.options.0.choices.0.cost_adjustment'));
        $this->post(route('checkout.store', [$store, $period]), [...$this->data($period), 'total' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::sole();
        $this->assertSame(42000, $order->total);
        $this->assertSame(2, $period->items()->sole()->ordered_quantity);
        $this->assertNotSame($order->access_token, $order->getRawOriginal('access_token'));
        $this->get(route('order.receipt', $order->access_token))->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertInertia(fn (Assert $page) => $page
            ->where('order.total', 42000)->where('order.pickup', null)->missing('order.items.0.private_costing')->missing('order.access_token')->missing('order.items.0.choices.0.cost_adjustment'));
        $this->get(route('order.receipt', str_repeat('a', 64)))->assertNotFound();
    }

    public function test_quota_is_checked_across_lines_and_orders_without_partial_updates(): void
    {
        $period = $this->period();
        $data = $this->data($period);
        $url = route('checkout.store', [$period->store, $period]);
        $line = $data['items'][0];
        $this->post($url, [...$data, 'items' => [$line, $line]])->assertSessionHasErrors('items');
        $this->assertDatabaseCount('rintis_orders', 0);
        $this->assertSame(0, $period->items()->sole()->ordered_quantity);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $this->post($url, $this->data($period))->assertSessionHasErrors('items');
        $this->assertDatabaseCount('rintis_orders', 1);
        $this->assertSame(2, $period->items()->sole()->ordered_quantity);
    }

    public function test_repeated_checkout_does_not_duplicate_order_even_after_session_closes(): void
    {
        $period = $this->period();
        $data = $this->data($period);
        $url = route('checkout.store', [$period->store, $period]);
        $first = $this->post($url, $data)->assertSessionHasNoErrors();
        $period->update(['status' => 'closed']);
        $this->post($url, $data)->assertSessionHasNoErrors()->assertRedirect($first->headers->get('Location'));
        $this->assertDatabaseCount('rintis_orders', 1);
        $this->assertSame(2, $period->items()->sole()->ordered_quantity);
        $this->expectException(ValidationException::class);
        app(PlaceOrder::class)->handle($period, $data, 'different-guest', null);
    }

    public function test_required_unknown_duplicate_and_multiple_single_choices_are_rejected(): void
    {
        $period = $this->period();
        $data = $this->data($period);
        $item = $period->items()->sole();
        $url = route('checkout.store', [$period->store, $period]);
        foreach ([[], [(string) Str::uuid()], [$item->options[0]['choices'][0]['id'], $item->options[0]['choices'][0]['id']], array_column($item->options[0]['choices'], 'id')] as $choices) {
            $data['items'][0]['choices'] = $choices;
            $this->post($url, $data)->assertSessionHasErrors('items');
        }
        $data['items'][0]['price'] = 1;
        $this->post($url, $data)->assertSessionHasErrors('items.0');
        $this->assertDatabaseCount('rintis_orders', 0);
    }

    public function test_future_expired_closed_and_mismatched_store_sessions_reject_checkout(): void
    {
        $period = $this->period();
        $data = $this->data($period);
        $url = route('checkout.store', [$period->store, $period]);
        $original = $period->opens_at;
        $period->update(['opens_at' => now()->addHour()]);
        $this->post($url, $data)->assertSessionHasErrors('items');
        $period->update(['opens_at' => $original, 'closes_at' => now()->subMinute()]);
        $this->post($url, $data)->assertSessionHasErrors('items');
        $period->update(['closes_at' => now()->addHour(), 'status' => 'cancelled']);
        $this->post($url, $data)->assertSessionHasErrors('items');
        $other = Store::factory()->create();
        $this->get(route('checkout.show', [$other, $period]))->assertNotFound();
        $this->post(route('checkout.store', [$other, $period]), $data)->assertNotFound();
        $this->assertDatabaseCount('rintis_orders', 0);
    }

    public function test_delivery_uses_server_road_quote_and_rejects_changed_or_expired_destination(): void
    {
        Http::preventStrayRequests();
        config(['rintis.routing_url' => 'https://routing.test/route/v1/driving']);
        Http::fake(['routing.test/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 1200]]])]);
        $period = $this->period();
        $store = $period->store;
        $window = ['delivery_starts_at' => now()->addDays(2)->toIso8601String(), 'delivery_ends_at' => now()->addDays(2)->addHour()->toIso8601String()];
        $period->update(['fulfillment' => [...$period->fulfillment, ...$window]]);
        $quote = $this->postJson(route('checkout.quote', [$store, $period]), ['latitude' => 0.91, 'longitude' => 109.01])->assertOk()->assertJsonPath('fee', 2400)->json();
        $data = $this->data($period, ['method' => 'delivery', 'delivery_address' => 'Buyer address', 'latitude' => 0.91, 'longitude' => 109.01, 'quote_id' => $quote['id']]);
        $url = route('checkout.store', [$store, $period]);
        $this->post($url, [...$data, 'latitude' => 0.92])->assertSessionHasErrors('quote_id');
        $this->travel(16)->minutes();
        $this->post($url, $data)->assertSessionHasErrors('quote_id');
        $quote = $this->postJson(route('checkout.quote', [$store, $period]), ['latitude' => 0.91, 'longitude' => 109.01])->assertOk()->json();
        $this->post($url, [...$data, 'quote_id' => $quote['id'], 'delivery_fee' => 0])->assertSessionHasNoErrors();
        $this->assertSame(44400, Order::sole()->total);
        $period->update(['fulfillment' => []]);
        $this->assertSame($window['delivery_starts_at'], Order::sole()->fulfillment['delivery_starts_at']);
        $this->assertSame($window['delivery_ends_at'], Order::sole()->fulfillment['delivery_ends_at']);
        $this->get(route('order.receipt', Order::sole()->access_token))->assertOk()->assertInertia(fn (Assert $page) => $page->where('order.pickup', null)->where('order.delivery.address_line', 'Buyer address')->missing('store.latitude'));
    }

    public function test_delivery_no_route_out_of_range_disabled_method_and_unlisted_store_are_rejected(): void
    {
        Http::preventStrayRequests();
        $period = $this->period();
        $store = $period->store;
        $url = route('checkout.quote', [$store, $period]);
        $this->postJson($url, ['latitude' => 1, 'longitude' => 109])->assertUnprocessable()->assertJsonValidationErrors('delivery');
        config(['rintis.routing_url' => 'https://routing.test/route/v1/driving']);
        Http::fake(['routing.test/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 10]]])]);
        $this->postJson($url, ['latitude' => 1, 'longitude' => 109])->assertUnprocessable()->assertJsonValidationErrors('delivery');
        $settings = $period->fulfillment;
        $settings['pickup_enabled'] = false;
        $period->update(['fulfillment' => $settings]);
        $this->post(route('checkout.store', [$store, $period]), $this->data($period))->assertSessionHasErrors('method');
        $store->update(['is_listed' => false]);
        $this->get(route('checkout.show', [$store, $period]))->assertNotFound();
        $this->post(route('checkout.store', [$store, $period]), $this->data($period))->assertForbidden();
    }

    public function test_only_store_owner_can_read_orders_and_receipt_token_is_not_shared_in_admin(): void
    {
        $period = $this->period();
        $store = $period->store;
        $this->post(route('checkout.store', [$store, $period]), $this->data($period))->assertSessionHasNoErrors();
        $url = route('admin.open-orders.orders', [$store, $period]);
        $this->get($url)->assertRedirectToRoute('login');
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->get($url)->assertNotFound();
        $this->get(route('admin.open-orders.orders', [$other, $period]))->assertNotFound();
        $this->actingAs($store->owner)->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->has('orders.data', 1)->missing('orders.data.0.access_token')->missing('orders.data.0.items.0.private_costing'));
    }

    public function test_unlimited_quota_accepts_multiple_lines_and_repeated_choice_across_lines(): void
    {
        $period = $this->period();
        $item = $period->items()->sole();
        $item->update(['quota' => null]);
        $data = $this->data($period);
        $data['items'][0]['quantity'] = 5;
        $data['items'][] = $data['items'][0];
        $this->post(route('checkout.store', [$period->store, $period]), $data)->assertSessionHasNoErrors();
        $this->assertSame(210000, Order::sole()->total);
        $this->assertSame(10, $item->fresh()->ordered_quantity);
        $this->get(route('open-order', $period->store))->assertInertia(fn (Assert $page) => $page->where('items.0.remaining', null));
    }

    public function test_zero_fixed_delivery_fee_is_valid_but_an_unroutable_destination_is_not(): void
    {
        config(['rintis.routing_url' => 'https://routing.test/route/v1/driving']);
        Http::preventStrayRequests();
        Http::fake(['routing.test/*' => Http::sequence()->push(['code' => 'NoRoute'])->push(['code' => 'Ok', 'routes' => [['distance' => 6000]]])->push(['code' => 'Ok', 'routes' => [['distance' => 5000]]])]);
        $period = $this->period();
        $settings = $period->fulfillment;
        $settings['delivery_fee_mode'] = 'fixed';
        $settings['delivery_fixed_fee'] = 0;
        $period->update(['fulfillment' => $settings]);
        $url = route('checkout.quote', [$period->store, $period]);
        $this->postJson($url, ['latitude' => 1, 'longitude' => 109])->assertUnprocessable()->assertJsonValidationErrors('delivery');
        $this->postJson($url, ['latitude' => 1, 'longitude' => 109])->assertUnprocessable()->assertJsonValidationErrors('delivery');
        $this->postJson($url, ['latitude' => 1, 'longitude' => 109])->assertOk()->assertJsonPath('fee', 0)->assertJsonPath('distance_m', 5000);
        $this->assertDatabaseCount('rintis_orders', 0);
    }

    public function test_transfer_order_saves_selected_bank_snapshot_without_marking_payment_received(): void
    {
        $period = $this->period();
        $store = $period->store;
        $bank = $store->bankAccounts()->create(['bank_name' => 'Test Bank', 'account_holder' => 'Store Owner', 'account_number' => '00123456789', 'is_active' => true]);
        $this->get(route('checkout.show', [$store, $period]))->assertInertia(fn (Assert $page) => $page->where('bankAccounts.0.id', $bank->id)->missing('bankAccounts.0.account_number'));
        $data = $this->data($period, ['payment_method' => 'bank_transfer', 'bank_account_id' => $bank->id]);
        $this->post(route('checkout.store', [$store, $period]), $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('bank_transfer', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(42000, $order->total);
        $bank->update(['account_number' => '99999999999', 'is_active' => false]);
        $bank->delete();
        $this->get(route('order.receipt', $order->access_token))->assertOk()->assertInertia(fn (Assert $page) => $page->where('order.bank_account.account_number', '00123456789')->where('order.payment_status', 'unpaid'));
        $this->post(route('checkout.store', [$store, $period]), $data)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('rintis_orders', 1);
    }

    public function test_transfer_requires_active_bank_owned_by_this_store_without_consuming_quota(): void
    {
        $period = $this->period();
        $store = $period->store;
        $url = route('checkout.store', [$store, $period]);
        $data = $this->data($period, ['payment_method' => 'bank_transfer']);
        $this->post($url, $data)->assertSessionHasErrors('bank_account_id');
        $foreign = Store::factory()->create()->bankAccounts()->create(['bank_name' => 'Other Bank', 'account_holder' => 'Other Seller', 'account_number' => '123456789', 'is_active' => true]);
        $this->post($url, [...$data, 'bank_account_id' => $foreign->id])->assertSessionHasErrors('bank_account_id');
        $inactive = $store->bankAccounts()->create(['bank_name' => 'Inactive', 'account_holder' => 'Owner', 'account_number' => '111111111', 'is_active' => false]);
        $this->post($url, [...$data, 'bank_account_id' => $inactive->id])->assertSessionHasErrors('bank_account_id');
        $this->assertDatabaseCount('rintis_orders', 0);
        $this->assertSame(0, $period->items()->sole()->ordered_quantity);
    }

    public function test_cash_needs_no_bank_and_does_not_copy_an_irrelevant_account(): void
    {
        $period = $this->period();
        $data = $this->data($period, ['bank_account_id' => 999999]);
        $this->post(route('checkout.store', [$period->store, $period]), $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('cash', $order->payment_method);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertNull($order->payment_details);
        $this->assertNull($order->receipt()['bank_account']);
    }

    public function test_pickup_progress_is_sequential_idempotent_and_visible_to_guest_without_private_actor_data(): void
    {
        $period = $this->period();
        $store = $period->store;
        $this->post(route('checkout.store', [$store, $period]), $this->data($period))->assertSessionHasNoErrors();
        $order = Order::sole();
        $url = route('admin.open-orders.order-status', [$store, $period, $order]);
        $this->actingAs($store->owner)->patch($url, ['status' => 'ready', 'expected_status' => 'received'])->assertSessionHasErrors('status');
        $this->patch($url, ['status' => 'preparing', 'expected_status' => 'received'])->assertSessionHasNoErrors();
        $this->patch($url, ['status' => 'preparing', 'expected_status' => 'received'])->assertSessionHasNoErrors();
        $this->assertCount(2, $order->fresh()->fulfillment_history);
        $this->assertNull($order->fresh()->receipt()['pickup']);
        $this->patch($url, ['status' => 'ready', 'expected_status' => 'received'])->assertSessionHasErrors('status');
        $this->patch($url, ['status' => 'ready', 'expected_status' => 'preparing'])->assertSessionHasNoErrors();
        $this->assertSame('ready', $order->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->patch($url, ['status' => 'out_for_delivery', 'expected_status' => 'ready'])->assertSessionHasErrors('status');
        $this->patch($url, ['status' => 'completed', 'expected_status' => 'ready'])->assertSessionHasErrors('status');
        $this->post(route('logout'));
        $this->get(route('order.receipt', $order->access_token), ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')), 'X-Inertia-Partial-Component' => 'Storefront/Receipt', 'X-Inertia-Partial-Data' => 'order'])
            ->assertOk()->assertJsonPath('props.order.pickup.address_line', $store->address_line)->assertJsonPath('props.order.status', 'ready')->assertJsonPath('props.order.progress.1.status', 'preparing')->assertJsonPath('props.order.progress.2.status', 'ready')->assertJsonMissingPath('props.order.progress.1.actor_id');
        $this->assertCount(3, $order->fresh()->fulfillment_history);
        $this->assertSame(2, $period->items()->sole()->ordered_quantity);
    }

    public function test_guest_foreign_seller_and_mismatched_parents_cannot_change_order_progress(): void
    {
        $period = $this->period();
        $store = $period->store;
        $this->post(route('checkout.store', [$store, $period]), $this->data($period))->assertSessionHasNoErrors();
        $order = Order::sole();
        $data = ['status' => 'preparing', 'expected_status' => 'received'];
        $url = route('admin.open-orders.order-status', [$store, $period, $order]);
        $this->patch($url, $data)->assertRedirectToRoute('login');
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->patch($url, $data)->assertNotFound();
        $this->patch(route('admin.open-orders.order-status', [$other, $period, $order]), $data)->assertNotFound();
        $foreignPeriod = $other->openOrders()->create(['name' => 'Other', 'status' => 'closed', 'opens_at' => now(), 'closes_at' => now()->addDay(), 'fulfillment' => []]);
        $this->actingAs($store->owner)->patch(route('admin.open-orders.order-status', [$store, $foreignPeriod, $order]), $data)->assertNotFound();
        $this->assertSame('received', $order->fresh()->status);
    }

    public function test_delivery_can_dispatch_after_ready_but_unpaid_bank_transfer_cannot_prepare(): void
    {
        $period = $this->period();
        $store = $period->store;
        $this->post(route('checkout.store', [$store, $period]), $this->data($period))->assertSessionHasNoErrors();
        $order = Order::sole();
        $order->method = 'delivery';
        $order->payment_method = 'bank_transfer';
        $order->save();
        $url = route('admin.open-orders.order-status', [$store, $period, $order]);
        $this->actingAs($store->owner)->patch($url, ['status' => 'preparing', 'expected_status' => 'received'])->assertSessionHasErrors('status');
        $this->assertSame('received', $order->fresh()->status);
        $order->payment_status = 'paid';
        $order->save();
        foreach ([['received', 'preparing'], ['preparing', 'ready'], ['ready', 'out_for_delivery']] as [$from, $to]) {
            $this->patch($url, ['status' => $to, 'expected_status' => $from])->assertSessionHasNoErrors();
        }
        $this->assertSame('out_for_delivery', $order->fresh()->status);
        $this->assertCount(4, $order->fresh()->fulfillment_history);
    }

    public function test_multiple_products_and_priced_options_are_added_to_one_checkout_total(): void
    {
        $period = $this->period();
        $store = $period->store;
        $category = $store->catalogCategories()->sole();
        $product = $store->catalogProducts()->create(['name' => 'Cookie', 'catalog_category_id' => $category->id, 'price' => 10000]);
        $cookie = $period->items()->create(['catalog_product_id' => $product->id, 'name' => 'Cookie', 'price' => 10000, 'quota' => null, 'options' => []]);
        $coffee = $period->items()->where('name', 'Americano')->sole();
        $data = ['idempotency_key' => (string) Str::uuid(), 'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'pickup', 'payment_method' => 'cash', 'items' => [
            ['id' => $coffee->id, 'quantity' => 2, 'choices' => [$coffee->options[0]['choices'][0]['id']]],
            ['id' => $cookie->id, 'quantity' => 3, 'choices' => []],
        ]];
        $this->post(route('checkout.store', [$store, $period]), $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame(72000, $order->total);
        $this->assertCount(2, $order->items);
        $this->assertSame(21000, $order->items[0]['unit_price']);
        $this->assertSame(2, $coffee->fresh()->ordered_quantity);
        $this->assertSame(3, $cookie->fresh()->ordered_quantity);
    }

    public function test_seller_payment_controls_are_enforced_at_checkout_and_on_stale_submit(): void
    {
        $period = $this->period();
        $store = $period->store;
        $bank = $store->bankAccounts()->create(['bank_name' => 'Test Bank', 'account_holder' => 'Owner', 'account_number' => '123456789', 'is_active' => true]);
        $show = route('checkout.show', [$store, $period]);
        $submit = route('checkout.store', [$store, $period]);
        $store->update(['cash_enabled' => false, 'bank_transfer_enabled' => true]);
        $this->get($show)->assertOk()->assertInertia(fn (Assert $page) => $page->where('paymentMethods.cash', false)->where('paymentMethods.bankTransfer', false)->where('paymentMethods.qris', false));
        $this->post($submit, $this->data($period))->assertSessionHasErrors('payment_method');
        $store->update(['cash_enabled' => true, 'bank_transfer_enabled' => false]);
        $this->get($show)->assertOk()->assertInertia(fn (Assert $page) => $page->where('paymentMethods.cash', true)->where('paymentMethods.bankTransfer', false)->has('bankAccounts', 0));
        $this->post($submit, $this->data($period, ['payment_method' => 'bank_transfer', 'bank_account_id' => $bank->id]))->assertSessionHasErrors('payment_method');
        $store->update(['cash_enabled' => false]);
        $this->get($show)->assertOk()->assertInertia(fn (Assert $page) => $page->where('paymentMethods.cash', false)->where('paymentMethods.bankTransfer', false));
        $this->post($submit, $this->data($period))->assertSessionHasErrors('payment_method');
        $this->assertDatabaseCount('rintis_orders', 0);
        $this->assertSame(0, $period->items()->sole()->ordered_quantity);
        $store->update(['bank_transfer_enabled' => true]);
        $this->post($submit, $this->data($period, ['payment_method' => 'bank_transfer', 'bank_account_id' => $bank->id]))->assertSessionHasNoErrors();
        $this->assertSame('bank_transfer', Order::sole()->payment_method);
    }

    #[TestWith(['unpaid'])]
    #[TestWith(['paid'])]
    public function test_session_with_orders_cannot_be_cancelled_but_can_stop_without_changing_orders(string $paymentStatus): void
    {
        $period = $this->period();
        $store = $period->store;
        $this->post(route('checkout.store', [$store, $period]), $this->data($period))->assertSessionHasNoErrors();
        $order = Order::sole();
        $order->payment_status = $paymentStatus;
        $order->save();
        $before = $order->getRawOriginal();
        $this->actingAs($store->owner)->get(route('admin.open-orders.index', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('periods.data.0.cancelUrl', null)->has('periods.data.0.closeUrl'));
        $cancel = route('admin.open-orders.cancel', [$store, $period]);
        $this->patch($cancel)->assertSessionHasErrors('session');
        $this->assertSame('published', $period->fresh()->status);
        $stop = route('admin.open-orders.close', [$store, $period]);
        $this->patch($stop)->assertSessionHasNoErrors();
        $this->patch($stop)->assertSessionHasNoErrors();
        $this->assertSame('closed', $period->fresh()->status);
        $this->assertSame($before, $order->fresh()->getRawOriginal());
        $this->assertSame(2, $period->items()->sole()->ordered_quantity);
        $this->patch($cancel)->assertSessionHasErrors('session');
        $this->post(route('checkout.store', [$store, $period]), $this->data($period))->assertSessionHasErrors('items');
        $this->assertDatabaseCount('rintis_orders', 1);
        $this->patch(route('admin.open-orders.order-status', [$store, $period, $order]), ['status' => 'preparing', 'expected_status' => 'received'])->assertSessionHasNoErrors();
        $this->assertSame('preparing', $order->fresh()->status);
        $this->assertSame($paymentStatus, $order->fresh()->payment_status);
    }
}
