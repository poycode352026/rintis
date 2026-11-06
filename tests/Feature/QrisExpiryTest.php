<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Services\ActiveCheckout;
use App\Services\ExpireQrisOrder;
use App\Services\MidtransPayment;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class QrisExpiryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function order(string $method = 'qris', string $payment = 'unpaid'): Order
    {
        $store = Store::factory()->create(['slug' => 'expiry-store', 'is_listed' => true, 'cash_enabled' => true]);
        $period = $store->openOrders()->create(['name' => 'Session', 'status' => 'published', 'opens_at' => CarbonImmutable::parse('2026-11-02T08:00:00+07:00')->utc(), 'closes_at' => CarbonImmutable::parse('2026-11-02T17:00:00+07:00')->utc(), 'fulfillment' => ['pickup_enabled' => true, 'delivery_enabled' => false]]);

        return Order::create(['store_id' => $store->id, 'open_order_id' => $period->id, 'reference' => 'RT-'.Str::random(12), 'idempotency_key' => (string) Str::uuid(), 'guest_key' => Str::random(64), 'access_hash' => hash('sha256', $token = Str::random(64)), 'access_token' => $token, 'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'pickup', 'payment_method' => $method, 'payment_status' => $payment, 'payment_details' => ['provider' => 'midtrans', 'payment_type' => 'qris', 'provider_order_id' => 'TEST-ORDER', 'expires_at' => '2026-11-02T16:45:00+07:00', 'status' => 'pending'], 'items' => [['name' => 'Coffee', 'quantity' => 1, 'unit_price' => 17000, 'line_total' => 17000, 'choices' => []]], 'fulfillment' => [], 'subtotal' => 17000, 'delivery_fee' => 0, 'total' => 17000])->refresh();
    }

    public function test_cutoff_expires_only_unpaid_qris_once_and_preserves_cash_and_paid_orders(): void
    {
        $this->travelTo(new \DateTimeImmutable('2026-11-02T16:44:59+07:00'));
        config(['midtrans.server_key' => null]);
        $qris = $this->order();
        $cash = $qris->replicate(['reference', 'idempotency_key', 'access_hash', 'access_token']);
        $cash->reference = 'RT-CASH';
        $cash->idempotency_key = (string) Str::uuid();
        $cash->access_hash = hash('sha256', Str::random(64));
        $cash->access_token = Str::random(64);
        $cash->payment_method = 'cash';
        $cash->save();
        $paid = $cash->replicate(['reference', 'idempotency_key', 'access_hash', 'access_token']);
        $paid->reference = 'RT-PAID';
        $paid->idempotency_key = (string) Str::uuid();
        $paid->access_hash = hash('sha256', Str::random(64));
        $paid->access_token = Str::random(64);
        $paid->payment_method = 'qris';
        $paid->payment_status = 'paid';
        $paid->save();
        $this->assertFalse(app(ExpireQrisOrder::class)->handle($qris));
        $this->travel(1)->seconds();

        $this->artisan('rintis:expire-qris')->expectsOutput('1 QRIS orders expired.')->assertSuccessful();
        $this->artisan('rintis:expire-qris')->expectsOutput('0 QRIS orders expired.')->assertSuccessful();
        $this->assertSame('expired', $qris->fresh()->status);
        $this->assertSame('received', $cash->fresh()->status);
        $this->assertSame('received', $paid->fresh()->status);
        $this->assertCount(2, $qris->fresh()->fulfillment_history);
        $this->assertNull($qris->fresh()->nextStatus());
    }

    public function test_receipt_and_history_show_expired_and_it_no_longer_blocks_buyer_or_next_session(): void
    {
        $this->travelTo(new \DateTimeImmutable('2026-11-02T16:45:00+07:00'));
        config(['midtrans.server_key' => null]);
        $order = $this->order();
        $this->get(route('order.receipt', $order->access_token))->assertOk()->assertInertia(fn (Assert $page) => $page->where('order.status', 'expired')->where('order.reviewUrl', null)->where('order.pickupPass', null));
        $this->assertNull(app(ActiveCheckout::class)->find($order->guest_key, null));
        $this->withSession(['rintis.guest_key' => $order->guest_key])->get(route('orders.history'))->assertInertia(fn (Assert $page) => $page->where('orders.data.0.status', 'expired'));
        $this->travelTo(new \DateTimeImmutable('2026-11-02T17:01:00+07:00'));
        $this->actingAs($order->store->owner)->get(route('admin.open-orders.index', $order->store))->assertInertia(fn (Assert $page) => $page->where('sessionBlocked', false));
        $this->get(route('admin.open-orders.orders', [$order->store, $order->openOrder, 'view' => 'expired']))->assertInertia(fn (Assert $page) => $page->where('orders.total', 1)->where('viewCounts.unpaid', 0)->where('viewCounts.expired', 1));
    }

    public function test_provider_payment_confirmation_is_checked_before_expiring_order(): void
    {
        $this->travelTo(new \DateTimeImmutable('2026-11-02T16:45:00+07:00'));
        config(['midtrans.server_key' => 'test-key', 'midtrans.merchant_id' => 'TEST-MERCHANT', 'midtrans.store_slug' => 'expiry-store']);
        Http::preventStrayRequests();
        Http::fake(['*/v2/TEST-ORDER/status' => Http::response(['status_code' => '200', 'order_id' => 'TEST-ORDER', 'merchant_id' => 'TEST-MERCHANT', 'currency' => 'IDR', 'gross_amount' => '17000.00', 'payment_type' => 'qris', 'transaction_status' => 'settlement'])]);
        $order = $this->order();

        $this->assertFalse(app(ExpireQrisOrder::class)->handle($order));
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('received', $order->fresh()->status);
        Http::assertSentCount(1);
    }

    public function test_expired_order_retry_never_creates_new_qris_charge(): void
    {
        $this->travelTo(new \DateTimeImmutable('2026-11-02T16:45:00+07:00'));
        config(['midtrans.server_key' => 'test-key', 'midtrans.merchant_id' => 'TEST-MERCHANT', 'midtrans.store_slug' => 'expiry-store']);
        Http::preventStrayRequests();
        Http::fake(['*/v2/TEST-ORDER/status' => Http::response(['status_code' => '404'], 404)]);
        $order = $this->order();
        app(ExpireQrisOrder::class)->handle($order);
        app(MidtransPayment::class)->sync($order->fresh(), create: true);

        $this->assertSame('expired', $order->fresh()->status);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_expired_qris_checkout_can_be_replaced_by_cash_before_session_closes(): void
    {
        $this->travelTo(new \DateTimeImmutable('2026-11-02T16:45:00+07:00'));
        config(['midtrans.server_key' => null]);
        $order = $this->order();
        $category = $order->store->catalogCategories()->create(['name' => 'Coffee']);
        $product = $order->store->catalogProducts()->create(['name' => 'Coffee', 'catalog_category_id' => $category->id, 'price' => 17000]);
        $item = $order->openOrder->items()->create(['catalog_product_id' => $product->id, 'name' => 'Coffee', 'price' => 17000, 'options' => []]);
        $this->withSession(['rintis.guest_key' => $order->guest_key])->get(route('checkout.show', [$order->store, $order->openOrder]))->assertInertia(fn (Assert $page) => $page->where('resetCheckoutKey', true)->where('paymentMethods.qris', false)->where('paymentMethods.cash', true));
        $this->post(route('checkout.store', [$order->store, $order->openOrder]), ['idempotency_key' => (string) Str::uuid(), 'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'pickup', 'payment_method' => 'cash', 'items' => [['id' => $item->id, 'quantity' => 1, 'choices' => []]]])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame('expired', $order->fresh()->status);
        $this->assertSame(2, Order::count());
        $this->assertSame('cash', Order::latest('id')->first()->payment_method);
    }
}
