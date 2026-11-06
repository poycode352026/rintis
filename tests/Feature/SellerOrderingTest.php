<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\WeeklySchedule;
use App\Services\OpenWeeklySchedule;
use App\Services\SaveOpenOrder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SellerOrderingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function ready(): Store
    {
        $store = Store::factory()->create(['is_listed' => true, 'pickup_enabled' => true, 'delivery_enabled' => false, 'latitude' => 0.9, 'longitude' => 109, 'cash_enabled' => true]);
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $store->catalogProducts()->create(['name' => 'Americano', 'price' => 8000, 'catalog_category_id' => $category->id, 'is_visible' => true]);

        return $store;
    }

    private function period(Store $store)
    {
        return app(SaveOpenOrder::class)->handle($store, ['name' => 'Today', 'intent' => 'publish', 'opens_at' => now(config('rintis.timezone'))->subHour()->format('Y-m-d\TH:i'), 'closes_at' => now(config('rintis.timezone'))->addHour()->format('Y-m-d\TH:i'), 'pickup_enabled' => true, 'delivery_enabled' => false, 'items' => [['catalog_product_id' => $store->catalogProducts()->sole()->id, 'price' => 8000, 'quota' => 5]]]);
    }

    private function data($period): array
    {
        return ['idempotency_key' => (string) Str::uuid(), 'method' => 'pickup', 'payment_method' => 'cash', 'cash_received' => 10000, 'items' => [['id' => $period->items()->sole()->id, 'quantity' => 1, 'choices' => []]]];
    }

    public function test_seller_can_create_multiple_walk_in_orders_and_cash_is_recorded_once(): void
    {
        $store = $this->ready();
        $period = $this->period($store);
        $url = route('admin.walk-in.store', [$store, $period]);
        $data = $this->data($period);
        $this->actingAs($store->owner)->get(route('admin.walk-in.create', [$store, $period]))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/OpenOrders/WalkIn')->where('cash', true)->where('catalogUrl', route('admin.catalog.index', $store))->where('settingsUrl', route('admin.stores.edit', $store)));
        $this->post($url, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->post($url, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->post($url, $this->data($period))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(2, Order::count());
        $order = Order::first();
        $this->assertNull($order->customer_id);
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('received', $order->status);
        $this->assertSame(10000, $order->cash_received);
        $this->assertSame(2000, $order->cash_change);
        $this->assertSame(2, $period->items()->sole()->ordered_quantity);
        $this->get(route('order.receipt', $order->access_token))->assertInertia(fn (Assert $page) => $page->where('order.items.0.open_order_item_id', $period->items()->sole()->id));
    }

    public function test_walk_in_can_keep_change_and_create_link_is_inside_session_orders(): void
    {
        $store = $this->ready();
        $period = $this->period($store);
        $this->actingAs($store->owner)->get(route('admin.open-orders.orders', [$store, $period]))->assertInertia(fn (Assert $page) => $page->where('walkInUrl', route('admin.walk-in.create', [$store, $period])));
        $this->post(route('admin.walk-in.store', [$store, $period]), [...$this->data($period), 'cash_received' => 30000, 'keep_change' => true])->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame(30000, $order->cash_received);
        $this->assertSame(0, $order->cash_change);
        $this->assertSame(8000, $order->total);
    }

    public function test_weekly_schedule_keeps_catalog_and_settings_navigation(): void
    {
        $store = $this->ready();
        $this->actingAs($store->owner)->get(route('admin.weekly.edit', $store))
            ->assertOk()->assertInertia(fn (Assert $page) => $page
            ->component('Admin/OpenOrders/WeeklySchedule')
            ->where('catalogUrl', route('admin.catalog.index', $store))
            ->where('settingsUrl', route('admin.stores.edit', $store)));
    }

    public function test_walk_in_qris_uses_verified_gateway_and_preserves_payment_cutoff(): void
    {
        $store = $this->ready();
        $store->update(['bank_transfer_enabled' => true]);
        config(['midtrans.server_key' => 'test-secret', 'midtrans.merchant_id' => 'TEST-MERCHANT', 'midtrans.store_slug' => $store->slug]);
        Http::preventStrayRequests();
        $period = $this->period($store);
        $data = [...$this->data($period), 'payment_method' => 'qris'];
        $id = 'RINTIS-'.str_replace('-', '', $data['idempotency_key']);
        Http::fake(['*/v2/*/status' => Http::response(['status_code' => '404'], 404), '*/v2/charge' => Http::response(['status_code' => '201', 'order_id' => $id, 'merchant_id' => 'TEST-MERCHANT', 'currency' => 'IDR', 'gross_amount' => '8000.00', 'payment_type' => 'qris', 'transaction_status' => 'pending', 'actions' => [['name' => 'generate-qr-code', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/test-qr/qr-code']]], 201)]);
        $this->actingAs($store->owner)->post(route('admin.walk-in.store', [$store, $period]), $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::sole();
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/test-qr/qr-code', $order->payment_details['qr_url']);
        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['payment_type'] === 'qris' && $request['transaction_details']['gross_amount'] === 8000);
        $this->travelTo($period->closes_at->subMinutes(10));
        $this->post(route('admin.walk-in.store', [$store, $period]), [...$data, 'idempotency_key' => (string) Str::uuid()])->assertSessionHasErrors('payment_method');
        $this->assertSame(1, Order::count());
    }

    public function test_short_cash_foreign_menu_closed_session_and_other_owner_cannot_create_order(): void
    {
        $store = $this->ready();
        $period = $this->period($store);
        $other = $this->ready();
        $otherPeriod = $this->period($other);
        $url = route('admin.walk-in.store', [$store, $period]);
        $this->actingAs($other->owner)->post($url, $this->data($period))->assertNotFound();
        $this->actingAs($store->owner)->post($url, [...$this->data($period), 'cash_received' => 1])->assertSessionHasErrors('cash_received');
        $this->post($url, $this->data($otherPeriod))->assertSessionHasErrors('items');
        $period->update(['status' => 'closed']);
        $this->post($url, $this->data($period))->assertSessionHasErrors('items');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, $period->items()->sole()->ordered_quantity);
    }

    public function test_weekly_schedule_opens_once_with_current_prices_and_does_not_reopen_after_stop(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 2)->setTimezone('Asia/Jakarta')->setTime(9, 0));
        $store = $this->ready();
        $product = $store->catalogProducts()->sole();
        $schedule = WeeklySchedule::create(['store_id' => $store->id, 'enabled' => true, 'days' => array_fill(0, 7, ['enabled' => true, 'opens' => '08:00', 'closes' => '17:00']), 'product_ids' => [$product->id]]);
        $this->artisan('rintis:open-weekly')->assertSuccessful();
        $this->artisan('rintis:open-weekly')->assertSuccessful();
        $this->assertSame(1, $store->openOrders()->count());
        $period = $store->openOrders()->sole();
        $this->assertSame(8000, $period->items()->sole()->price);
        $this->assertSame('2026-11-02T17:00', $period->closes_at->setTimezone('Asia/Jakarta')->format('Y-m-d\TH:i'));
        $period->update(['status' => 'closed']);
        app(OpenWeeklySchedule::class)->handle($schedule);
        $this->assertSame(1, $store->openOrders()->count());
        $this->travel(1)->days();
        app(OpenWeeklySchedule::class)->handle($schedule);
        $this->assertSame(2, $store->openOrders()->count());
    }

    public function test_daily_menus_are_saved_and_only_todays_products_open_while_closed_days_stay_closed(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 2)->setTimezone('Asia/Jakarta')->setTime(9, 0));
        $store = $this->ready();
        $coffee = $store->catalogProducts()->sole();
        $tea = $store->catalogProducts()->create(['name' => 'Tea', 'price' => 6000, 'catalog_category_id' => $coffee->catalog_category_id]);
        $days = array_fill(0, 7, ['enabled' => false, 'opens' => '08:00', 'closes' => '17:00', 'product_ids' => []]);
        $days[0] = [...$days[0], 'enabled' => true, 'product_ids' => [$coffee->id]];
        $days[1] = [...$days[1], 'enabled' => true, 'product_ids' => [$tea->id]];
        $url = route('admin.weekly.update', $store);
        $this->actingAs($store->owner)->put($url, ['enabled' => true, 'days' => $days])->assertSessionHasNoErrors();
        $schedule = WeeklySchedule::sole();
        $this->assertSame([$coffee->id], $schedule->days[0]['product_ids']);
        $this->assertSame([], $schedule->product_ids);
        app(OpenWeeklySchedule::class)->handle($schedule);
        $monday = $store->openOrders()->sole();
        $this->assertSame($coffee->id, $monday->items()->sole()->catalog_product_id);
        $monday->update(['status' => 'closed']);
        $this->travel(1)->days();
        app(OpenWeeklySchedule::class)->handle($schedule);
        $tuesday = $store->openOrders()->latest('id')->first();
        $this->assertSame($tea->id, $tuesday->items()->sole()->catalog_product_id);
        $tuesday->update(['status' => 'closed']);
        $this->travel(1)->days();
        app(OpenWeeklySchedule::class)->handle($schedule);
        $this->assertSame(2, $store->openOrders()->count());
        $invalid = $days;
        $invalid[0]['product_ids'] = [];
        $this->put($url, ['enabled' => true, 'days' => $invalid])->assertSessionHasErrors('days.0.product_ids');
        $invalid[0]['product_ids'] = [$coffee->id, $coffee->id];
        $this->put($url, ['enabled' => true, 'days' => $invalid])->assertSessionHasErrors('days.0.product_ids');
        $this->assertSame([$coffee->id], $schedule->fresh()->days[0]['product_ids']);
    }

    public function test_schedule_validation_is_scoped_and_disabled_skipped_or_blocked_schedule_does_not_open(): void
    {
        $this->travelTo(now()->setDate(2026, 11, 2)->setTimezone('Asia/Jakarta')->setTime(9, 0));
        $store = $this->ready();
        $other = $this->ready();
        $days = array_fill(0, 7, ['enabled' => true, 'opens' => '08:00', 'closes' => '17:00']);
        $url = route('admin.weekly.update', $store);
        $this->actingAs($other->owner)->put($url, ['enabled' => true, 'days' => $days, 'product_ids' => [$other->catalogProducts()->sole()->id]])->assertNotFound();
        $this->actingAs($store->owner)->put($url, ['enabled' => true, 'days' => $days, 'product_ids' => [$other->catalogProducts()->sole()->id]])->assertSessionHasErrors('days.0.product_ids.0');
        $bad = $days;
        $bad[0]['closes'] = '07:00';
        $this->put($url, ['enabled' => true, 'days' => $bad, 'product_ids' => [$store->catalogProducts()->sole()->id]])->assertSessionHasErrors('days.0.closes');
        $this->put($url, ['enabled' => false, 'days' => $days, 'product_ids' => [$store->catalogProducts()->sole()->id]])->assertSessionHasNoErrors();
        $schedule = WeeklySchedule::sole();
        app(OpenWeeklySchedule::class)->handle($schedule);
        $this->assertSame(0, $store->openOrders()->count());
        $schedule->update(['enabled' => true]);
        $this->post(route('admin.weekly.skip', $store))->assertRedirect();
        app(OpenWeeklySchedule::class)->handle($schedule);
        $this->assertSame(0, $store->openOrders()->count());
        $schedule->refresh()->update(['skip_date' => null]);
        $period = $this->period($store);
        app(OpenWeeklySchedule::class)->handle($schedule);
        $this->assertSame(1, $store->openOrders()->count());
        $this->assertNotNull($schedule->fresh()->last_error);
    }
}
