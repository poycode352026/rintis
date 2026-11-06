<?php

namespace Tests\Feature;

use App\Models\DeliveryRun;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DeliveryTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.ssr.enabled' => false, 'rintis.routing_demo' => false, 'rintis.routing_url' => 'https://routing.example/route/v1/driving']);
        Http::preventStrayRequests();
    }

    private function order(?Store $store = null, string $status = 'ready', string $payment = 'cash', string $paid = 'unpaid'): Order
    {
        $store ??= Store::factory()->create(['latitude' => 0.9, 'longitude' => 109]);
        $period = $store->openOrders()->first() ?? $store->openOrders()->create(['name' => 'Session', 'status' => 'closed', 'opens_at' => now()->subDay(), 'closes_at' => now()->subHour(), 'fulfillment' => []]);
        $order = Order::create(['store_id' => $store->id, 'open_order_id' => $period->id, 'reference' => 'RT-'.Str::upper(Str::random(12)),
            'idempotency_key' => (string) Str::uuid(), 'guest_key' => Str::random(64), 'access_hash' => hash('sha256', $token = Str::random(64)), 'access_token' => $token,
            'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'delivery', 'payment_method' => $payment, 'payment_status' => $paid,
            'items' => [['name' => 'Coffee', 'quantity' => 1, 'unit_price' => 17000, 'line_total' => 17000, 'choices' => [], 'private_costing' => ['secret' => 1000]]],
            'fulfillment' => ['address_line' => 'Private buyer address', 'latitude' => 1, 'longitude' => 109.1], 'subtotal' => 17000, 'delivery_fee' => 3000, 'total' => 20000]);
        $order->forceFill(['status' => $status])->save();

        return $order;
    }

    private function fakeMatrix(array $matrix): void
    {
        Http::fake(['routing.example/table/v1/driving/*' => Http::response(['code' => 'Ok', 'distances' => $matrix])]);
    }

    public function test_plan_uses_nearest_road_distance_from_each_previous_stop_and_persists_queue(): void
    {
        $a = $this->order();
        $store = $a->store;
        $b = $this->order($store);
        $c = $this->order($store);
        $this->fakeMatrix([[0, 100, 200, 300], [100, 0, 900, 150], [200, 50, 0, 100], [300, 150, 100, 0]]);
        $this->actingAs($store->owner)->post(route('admin.deliveries.plan', $store), ['orders' => [$c->id, $b->id, $a->id]])->assertSessionHasNoErrors()->assertRedirect();
        $run = DeliveryRun::sole();
        $this->assertSame([$a->id, $c->id, $b->id], array_column($run->stops, 'order_id'));
        $this->assertSame(350, $run->distance_m);
        $this->assertSame('ready', $a->fresh()->status);
        $this->get(route('admin.deliveries.index', $store))->assertInertia(fn (Assert $page) => $page->where('run.stops.0.id', $a->id)->where('run.stops.1.id', $c->id)->missing('run.stops.0.items.0.private_costing')->missing('run.stops.0.access_token'));
        Http::assertSentCount(1);
    }

    public function test_bulk_preparation_and_ready_are_atomic_and_block_unpaid_transfers(): void
    {
        $a = $this->order(status: 'received');
        $b = $this->order($a->store, 'received', 'bank_transfer');
        $this->actingAs($a->store->owner)->post(route('admin.deliveries.bulk', $a->store), ['orders' => [$a->id, $b->id], 'stage' => 'preparing'])->assertSessionHasErrors('status');
        $this->assertSame('received', $a->fresh()->status);
        $b->update(['payment_status' => 'paid']);
        foreach (['preparing', 'ready'] as $stage) {
            $this->post(route('admin.deliveries.bulk', $a->store), ['orders' => [$a->id, $b->id], 'stage' => $stage])->assertSessionHasNoErrors();
            $this->assertSame($stage, $a->fresh()->status);
            $this->assertSame($stage, $b->fresh()->status);
        }
        Http::assertNothingSent();
    }

    public function test_delivery_cash_kept_records_full_amount_and_zero_returned_change(): void
    {
        $order = $this->order();
        $store = $order->store;
        $this->fakeMatrix([[0, 100], [100, 0]]);
        $this->actingAs($store->owner)->post(route('admin.deliveries.plan', $store), ['orders' => [$order->id]])->assertSessionHasNoErrors();
        $run = DeliveryRun::sole();
        $this->post(route('admin.deliveries.start', [$store, $run]))->assertSessionHasNoErrors();
        $this->post(route('admin.deliveries.complete', [$store, $run]), ['order_id' => $order->id, 'cash_received' => 30000, 'keep_change' => true])->assertSessionHasNoErrors();
        $this->assertSame(30000, $order->fresh()->cash_received);
        $this->assertSame(0, $order->fresh()->cash_change);
        $this->assertSame(20000, $order->fresh()->total);
    }

    public function test_dispatch_and_complete_in_sequence_collects_cash_once_and_finishes_run(): void
    {
        $a = $this->order();
        $b = $this->order($a->store, payment: 'bank_transfer', paid: 'paid');
        $this->fakeMatrix([[0, 100, 200], [100, 0, 100], [200, 100, 0]]);
        $store = $a->store;
        $this->actingAs($store->owner);
        $this->post(route('admin.deliveries.plan', $store), ['orders' => [$a->id, $b->id]])->assertSessionHasNoErrors();
        $run = DeliveryRun::sole();
        $this->assertFalse($a->fresh()->receipt()['deliveryStarted']);
        $this->post(route('admin.deliveries.start', [$store, $run]))->assertSessionHasNoErrors();
        $this->assertSame('out_for_delivery', $a->fresh()->status);
        $this->assertTrue($a->fresh()->receipt()['deliveryStarted']);
        $this->post(route('admin.deliveries.start', [$store, $run]))->assertSessionHasNoErrors();
        $this->assertCount(1, collect($a->fresh()->fulfillment_history)->where('status', 'delivery_started'));
        $url = route('admin.deliveries.complete', [$store, $run]);
        $this->post($url, ['order_id' => $b->id])->assertSessionHasErrors('order_id');
        $this->post($url, ['order_id' => $a->id, 'cash_received' => 1000])->assertSessionHasErrors('cash_received');
        $this->assertSame('unpaid', $a->fresh()->payment_status);
        $this->post($url, ['order_id' => $a->id, 'cash_received' => 25000])->assertSessionHasNoErrors();
        $this->assertSame(5000, $a->fresh()->cash_change);
        $this->post($url, ['order_id' => $a->id, 'cash_received' => 99999])->assertSessionHasNoErrors();
        $this->assertSame(25000, $a->fresh()->cash_received);
        $this->post($url, ['order_id' => $b->id])->assertSessionHasNoErrors();
        $this->assertSame('completed', $run->fresh()->status);
        $this->assertSame('completed', $b->fresh()->status);
        $this->assertSame('paid', $a->fresh()->payment_status);
    }

    public function test_tenant_isolation_and_guest_cannot_view_delivery_locations(): void
    {
        $a = $this->order();
        $other = $this->order();
        $this->get(route('admin.deliveries.index', $a->store))->assertRedirect(route('login'));
        $this->actingAs($other->store->owner)->get(route('admin.deliveries.index', $a->store))->assertNotFound();
        $this->actingAs($a->store->owner)->post(route('admin.deliveries.plan', $a->store), ['orders' => [$other->id]])->assertSessionHasErrors('orders');
        Http::assertNothingSent();
        $this->assertSame(0, DeliveryRun::count());
    }

    public function test_missing_road_route_or_invalid_pin_never_creates_fake_queue(): void
    {
        $a = $this->order();
        $this->actingAs($a->store->owner);
        $this->fakeMatrix([[0, null], [null, 0]]);
        $this->post(route('admin.deliveries.plan', $a->store), ['orders' => [$a->id]])->assertSessionHasErrors('orders');
        $this->assertSame(0, DeliveryRun::count());
        $a->update(['fulfillment' => ['address_line' => 'Missing pin']]);
        $this->post(route('admin.deliveries.plan', $a->store), ['orders' => [$a->id]])->assertSessionHasErrors('orders');
        Http::assertSentCount(1);
    }

    public function test_only_one_current_run_and_planned_route_can_be_cleared_without_changing_orders(): void
    {
        $a = $this->order();
        $store = $a->store;
        $this->actingAs($store->owner);
        $this->fakeMatrix([[0, 100], [100, 0]]);
        $url = route('admin.deliveries.plan', $store);
        $this->post($url, ['orders' => [$a->id]])->assertSessionHasNoErrors();
        $this->post($url, ['orders' => [$a->id]])->assertSessionHasErrors('orders');
        $this->post(route('admin.deliveries.clear', [$store, DeliveryRun::sole()]))->assertSessionHasNoErrors();
        $this->assertSame('discarded', DeliveryRun::sole()->status);
        $this->assertSame('ready', $a->fresh()->status);
    }

    public function test_session_bulk_prepares_both_pickup_and_delivery_and_only_shows_queue_when_ready(): void
    {
        $a = $this->order(status: 'received');
        $b = $this->order($a->store, 'received', 'bank_transfer', 'paid');
        $b->update(['method' => 'pickup']);
        $store = $a->store;
        $period = $a->openOrder;
        $this->actingAs($store->owner);
        $pageUrl = route('admin.open-orders.orders', [$store, $period]);
        $this->get($pageUrl)->assertInertia(fn (Assert $page) => $page->where('deliveryUrl', null));
        $url = route('admin.open-orders.bulk-status', [$store, $period]);
        foreach (['preparing', 'ready'] as $stage) {
            $this->post($url, ['orders' => [$a->id, $b->id], 'status' => $stage])->assertSessionHasNoErrors();
            $this->assertSame($stage, $a->fresh()->status);
            $this->assertSame($stage, $b->fresh()->status);
        }
        $this->get($pageUrl)->assertInertia(fn (Assert $page) => $page->where('deliveryUrl', route('admin.deliveries.index', $store)));
    }

    public function test_session_bulk_rejects_unpaid_transfer_or_foreign_session_without_partial_changes(): void
    {
        $a = $this->order(status: 'received');
        $b = $this->order($a->store, 'received', 'bank_transfer', 'unpaid');
        $this->actingAs($a->store->owner);
        $url = route('admin.open-orders.bulk-status', [$a->store, $a->openOrder]);
        $this->post($url, ['orders' => [$a->id, $b->id], 'status' => 'preparing'])->assertSessionHasErrors('status');
        $this->assertSame('received', $a->fresh()->status);
        $other = $this->order(status: 'received');
        $this->post($url, ['orders' => [$a->id, $other->id], 'status' => 'preparing'])->assertSessionHasErrors('orders');
        $this->assertSame('received', $other->fresh()->status);
    }

    public function test_select_all_includes_one_hundred_eligible_orders_across_pages_and_excludes_unpaid(): void
    {
        $first = $this->order(status: 'received');
        $store = $first->store;
        $ids = [$first->id];
        for ($i = 1; $i < 100; $i++) {
            $ids[] = $this->order($store, 'received')->id;
        }
        $unpaid = $this->order($store, 'received', 'bank_transfer', 'unpaid');
        $this->actingAs($store->owner);
        $this->get(route('admin.open-orders.orders', [$store, $first->openOrder]))->assertInertia(fn (Assert $page) => $page
            ->has('orders.data', 20)->has('selectableOrders', 100)->where('selectableOrders.0.id', $first->id)->missing('selectableOrders.0.phone'));
        $this->post(route('admin.open-orders.bulk-status', [$store, $first->openOrder]), ['orders' => $ids, 'status' => 'preparing'])->assertSessionHasNoErrors();
        $this->assertSame(100, $store->orders()->where('status', 'preparing')->count());
        $this->assertSame('received', $unpaid->fresh()->status);
    }

    public function test_ready_delivery_orders_are_selectable_and_dispatch_together_but_pickup_cannot_dispatch(): void
    {
        $a = $this->order();
        $store = $a->store;
        $b = $this->order($store, payment: 'bank_transfer', paid: 'paid');
        $pickup = $this->order($store);
        $pickup->update(['method' => 'pickup']);
        $this->actingAs($store->owner);
        $this->get(route('admin.open-orders.orders', [$store, $a->openOrder]).'?view=delivery')->assertInertia(fn (Assert $page) => $page->has('selectableOrders', 2)->where('selectableOrders.0.id', $a->id)->where('selectableOrders.1.id', $b->id));
        $url = route('admin.open-orders.bulk-status', [$store, $a->openOrder]);
        $this->post($url, ['orders' => [$a->id, $pickup->id], 'status' => 'out_for_delivery'])->assertSessionHasErrors('orders');
        $this->assertSame('ready', $a->fresh()->status);
        $this->post($url, ['orders' => [$a->id, $b->id], 'status' => 'out_for_delivery'])->assertSessionHasNoErrors();
        $this->assertSame('out_for_delivery', $a->fresh()->status);
        $this->assertFalse($a->fresh()->receipt()['deliveryStarted']);
        $this->assertSame('out_for_delivery', $b->fresh()->status);
        $this->assertSame('ready', $pickup->fresh()->status);
    }

    public function test_unfinished_orders_block_next_session_even_after_schedule_has_ended(): void
    {
        $order = $this->order();
        $store = $order->store;
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $product = $store->catalogProducts()->create(['name' => 'Coffee', 'catalog_category_id' => $category->id, 'price' => 17000]);
        $order->openOrder->update(['fulfillment' => ['pickup_enabled' => false, 'delivery_enabled' => true]]);
        $data = ['name' => 'Next session', 'intent' => 'publish', 'opens_at' => now()->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i'), 'closes_at' => now()->setTimezone('Asia/Jakarta')->addDay()->format('Y-m-d\TH:i'), 'pickup_enabled' => true, 'delivery_enabled' => false, 'items' => [['catalog_product_id' => $product->id, 'price' => 17000, 'quota' => null]]];
        $this->actingAs($store->owner)->post(route('admin.open-orders.store', $store), $data)->assertSessionHasErrors('name');
        $this->assertSame(1, $store->openOrders()->count());
        $this->get(route('admin.open-orders.index', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('sessionBlocked', true));
        $order->status = 'completed';
        $order->save();
        $this->get(route('admin.open-orders.index', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('sessionBlocked', false));
    }

    public function test_notification_polling_does_not_consume_delivery_planning_limit(): void
    {
        $order = $this->order();
        $store = $order->store;
        $this->actingAs($store->owner);
        for ($i = 0; $i < 12; $i++) {
            $this->getJson(route('admin.notifications.index'))->assertOk();
        }
        $this->fakeMatrix([[0, 100], [100, 0]]);

        $this->post(route('admin.deliveries.plan', $store), ['orders' => [$order->id]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1, DeliveryRun::count());
        Http::assertSentCount(1);
    }

    public function test_real_planning_limit_returns_inline_error_and_does_not_block_another_seller(): void
    {
        $order = $this->order();
        $store = $order->store;
        $url = route('admin.deliveries.plan', $store);
        $this->actingAs($store->owner);
        for ($i = 0; $i < 10; $i++) {
            $this->post($url, ['orders' => []])->assertSessionHasErrors('orders');
        }

        $response = $this->post($url, ['orders' => [$order->id]], ['X-Inertia' => 'true']);
        $response->assertStatus(303)->assertRedirect(route('admin.deliveries.index', $store))->assertSessionHasErrors('orders')->assertHeader('Retry-After');
        $this->assertStringContainsString('seconds before planning another delivery route', session('errors')->first('orders'));
        $this->assertSame(0, DeliveryRun::count());
        Http::assertNothingSent();

        $other = $this->order();
        $this->fakeMatrix([[0, 100], [100, 0]]);
        $this->actingAs($other->store->owner)->post(route('admin.deliveries.plan', $other->store), ['orders' => [$other->id]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(1, DeliveryRun::count());
    }
}
