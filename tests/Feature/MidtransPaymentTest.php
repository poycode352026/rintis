<?php

namespace Tests\Feature;

use App\Models\OpenOrder;
use App\Models\Order;
use App\Models\Store;
use App\Services\SaveOpenOrder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.ssr.enabled' => false, 'midtrans.server_key' => 'test-secret', 'midtrans.merchant_id' => 'TEST-MERCHANT', 'midtrans.store_slug' => 'wemeet-coffee']);
        Http::preventStrayRequests();
    }

    private function checkout(): array
    {
        $store = Store::factory()->create(['slug' => 'wemeet-coffee', 'is_listed' => true, 'pickup_enabled' => true, 'bank_transfer_enabled' => true, 'latitude' => 0.9, 'longitude' => 109]);
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $product = $store->catalogProducts()->create(['name' => 'Americano', 'price' => 17000, 'catalog_category_id' => $category->id]);
        app(SaveOpenOrder::class)->handle($store, ['name' => 'Weekend', 'intent' => 'publish', 'opens_at' => now()->setTimezone('Asia/Jakarta')->subHour()->format('Y-m-d\TH:i'), 'closes_at' => now()->setTimezone('Asia/Jakarta')->addDay()->format('Y-m-d\TH:i'), 'pickup_enabled' => true, 'delivery_enabled' => false, 'items' => [['catalog_product_id' => $product->id, 'price' => 17000, 'quota' => 10]]]);
        $period = $store->openOrders()->sole();
        $key = (string) Str::uuid();

        return [route('checkout.store', [$store, $period]), ['idempotency_key' => $key, 'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'pickup', 'payment_method' => 'bank_transfer', 'payment_bank' => 'bca', 'items' => [['id' => $period->items()->sole()->id, 'quantity' => 1, 'choices' => []]]], 'RINTIS-'.str_replace('-', '', $key)];
    }

    private function payload(string $id, array $overrides = []): array
    {
        return array_replace(['status_code' => '200', 'order_id' => $id, 'merchant_id' => 'TEST-MERCHANT', 'currency' => 'IDR', 'gross_amount' => '17000.00', 'payment_type' => 'bank_transfer', 'transaction_status' => 'pending', 'va_numbers' => [['bank' => 'bca', 'va_number' => '123456789012']]], $overrides);
    }

    public function test_checkout_creates_va_automatically_and_duplicate_submit_reuses_it(): void
    {
        [$url, $data, $id] = $this->checkout();
        Http::fake(['*/v2/*/status' => Http::sequence()->push(['status_code' => '404'], 404)->push($this->payload($id)), '*/v2/charge' => Http::response($this->payload($id), 201)]);
        $this->post($url, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->post($url, $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::sole();
        $this->assertSame('123456789012', $order->payment_details['account_number']);
        $this->assertSame('unpaid', $order->payment_status);
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['transaction_details']['gross_amount'] === 17000);
        $this->get(route('order.receipt', $order->access_token))->assertInertia(fn (Assert $page) => $page->where('order.bank_account.account_number', '123456789012')->missing('server_key')->where('order.pickup', null));
    }

    public function test_webhook_ignores_claimed_paid_and_requires_authoritative_matching_status(): void
    {
        [$url, $data, $id] = $this->checkout();
        Http::fake(['*/v2/*/status' => Http::sequence()->push(['status_code' => '404'], 404)->push($this->payload($id))->push($this->payload($id, ['transaction_status' => 'settlement', 'gross_amount' => '1.00']))->push($this->payload($id, ['transaction_status' => 'settlement'])), '*/v2/charge' => Http::response($this->payload($id), 201)]);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $endpoint = route('payment.notification');
        $this->postJson($endpoint, ['order_id' => $id, 'transaction_status' => 'settlement'])->assertOk();
        $this->assertSame('unpaid', Order::sole()->payment_status);
        $this->postJson($endpoint, ['order_id' => $id])->assertStatus(503);
        $this->assertSame('unpaid', Order::sole()->payment_status);
        $this->postJson($endpoint, ['order_id' => $id])->assertOk();
        $this->assertSame('paid', Order::sole()->payment_status);
        $this->postJson($endpoint, ['order_id' => $id])->assertOk();
        Http::assertSentCount(5);
    }

    public function test_timeout_keeps_order_unpaid_and_retry_recovers_existing_va_without_charge(): void
    {
        [$url, $data, $id] = $this->checkout();
        Http::fake(['*/v2/*/status' => Http::sequence()->push(['status_code' => '404'], 404)->push($this->payload($id)), '*/v2/charge' => Http::failedConnection()]);
        $this->post($url, $data)->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::sole();
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertNull($order->payment_details['account_number']);
        $this->post(route('order.payment.retry', $order->access_token))->assertRedirect();
        $this->assertSame('123456789012', $order->fresh()->payment_details['account_number']);
        $this->assertSame(1, $order->openOrder->items()->sole()->ordered_quantity);
    }

    public function test_another_store_cannot_use_this_merchant_and_invalid_bank_is_rejected(): void
    {
        [$url, $data] = $this->checkout();
        $this->post($url, [...$data, 'payment_bank' => 'unknown'])->assertSessionHasErrors('payment_bank');
        config(['midtrans.store_slug' => 'another-store']);
        $this->post($url, $data)->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_poll_verifies_settlement_without_public_webhook_on_localhost(): void
    {
        [$url, $data, $id] = $this->checkout();
        Http::fake(['*/v2/*/status' => Http::sequence()->push(['status_code' => '404'], 404)->push($this->payload($id, ['transaction_status' => 'settlement'])), '*/v2/charge' => Http::response($this->payload($id), 201)]);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $this->travel(16)->seconds();
        $this->get(route('order.receipt', Order::sole()->access_token))->assertInertia(fn (Assert $page) => $page->where('order.payment_status', 'paid'));
        $this->assertSame('received', Order::sole()->status);
    }

    public function test_expired_or_wrong_merchant_status_does_not_mark_order_paid(): void
    {
        [$url, $data, $id] = $this->checkout();
        Http::fake(['*/v2/*/status' => Http::sequence()->push(['status_code' => '404'], 404)->push($this->payload($id, ['transaction_status' => 'settlement', 'merchant_id' => 'ANOTHER-MERCHANT']))->push($this->payload($id, ['transaction_status' => 'expire'])), '*/v2/charge' => Http::response($this->payload($id), 201)]);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $this->postJson(route('payment.notification'), ['order_id' => $id])->assertStatus(503);
        $this->postJson(route('payment.notification'), ['order_id' => $id])->assertOk();
        $this->assertSame('unpaid', Order::sole()->payment_status);
        $this->assertSame('expire', Order::sole()->payment_details['status']);
    }

    public function test_cash_and_unknown_webhooks_do_not_contact_midtrans(): void
    {
        [$url, $data] = $this->checkout();
        $this->post($url, [...$data, 'payment_method' => 'cash'])->assertSessionHasNoErrors();
        $this->postJson(route('payment.notification'), ['order_id' => 'unknown'])->assertNotFound();
        $this->assertSame('unpaid', Order::sole()->payment_status);
        Http::assertNothingSent();
    }

    public function test_numeric_not_found_status_creates_va_and_clears_previous_safe_error(): void
    {
        [$url, $data, $id] = $this->checkout();
        Http::fake(['*/v2/*/status' => Http::sequence()->push(['status_code' => 404])->push($this->payload($id)), '*/v2/charge' => Http::response(['status_code' => '403', 'status_message' => 'Sensitive raw provider text'], 200)]);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('credentials', $order->payment_details['last_error']['kind']);
        $this->assertStringNotContainsString('Sensitive', json_encode($order->payment_details));
        $this->assertSame('unpaid', $order->payment_status);
        $this->post(route('order.payment.retry', $order->access_token))->assertRedirect();
        $this->assertArrayNotHasKey('last_error', $order->fresh()->payment_details);
        $this->assertSame('123456789012', $order->fresh()->payment_details['account_number']);
        Http::assertSentCount(3);
    }

    public function test_connection_failure_is_recorded_without_exception_text_or_credentials(): void
    {
        [$url, $data] = $this->checkout();
        Http::fake(['*/v2/*/status' => Http::failedConnection()]);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $details = Order::sole()->payment_details;
        $this->assertSame('connection', $details['last_error']['kind']);
        $this->assertNull($details['last_error']['code']);
        $this->assertStringNotContainsString('test-secret', json_encode($details));
        $this->assertSame('unpaid', Order::sole()->payment_status);
        Http::assertSentCount(1);
    }

    public function test_http_401_without_json_is_identified_as_credentials_and_does_not_charge(): void
    {
        [$url, $data] = $this->checkout();
        Http::fake(['*/v2/*/status' => Http::response('<html>Unauthorized</html>', 401, ['Content-Type' => 'text/html'])]);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('credentials', $order->payment_details['last_error']['kind']);
        $this->assertSame(401, $order->payment_details['last_error']['http_status']);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertNull($order->payment_details['account_number']);
        Http::assertSentCount(1);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_manual_refresh_checks_provider_immediately_without_creating_another_charge(): void
    {
        [$url, $data, $id] = $this->checkout();
        Http::fake(['*/v2/*/status' => Http::sequence()->push(['status_code' => '404'], 404)->push($this->payload($id, ['transaction_status' => 'settlement'])), '*/v2/charge' => Http::response($this->payload($id), 201)]);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->post(route('order.payment.refresh', $order->access_token))->assertRedirect(route('order.receipt', $order->access_token));
        $this->assertSame('paid', $order->fresh()->payment_status);
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request->method() === 'GET' && str_ends_with($request->url(), '/status'));
    }

    public function test_qris_checkout_sets_cutoff_and_settlement_is_verified_without_va(): void
    {
        $this->travelTo(now()->startOfMinute());
        [$url, $data, $id] = $this->checkout();
        $data['payment_method'] = 'qris';
        unset($data['payment_bank']);
        $payload = $this->payload($id, ['payment_type' => 'qris', 'va_numbers' => [], 'actions' => [['name' => 'generate-qr-code', 'url' => 'https://api.sandbox.midtrans.com/v2/qris/test-qr/qr-code']]]);
        Http::fake(['*/v2/*/status' => Http::sequence()->push(['status_code' => '404'], 404)->push([...$payload, 'transaction_status' => 'settlement']), '*/v2/charge' => Http::response($payload, 201)]);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->assertSame('qris', $order->payment_method);
        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/test-qr/qr-code', $order->payment_details['qr_url']);
        Http::assertSent(fn ($request) => $request->method() === 'POST' && $request['payment_type'] === 'qris' && $request['qris']['acquirer'] === 'gopay' && $request['custom_expiry']['expiry_duration'] === 85500 && $request['custom_expiry']['unit'] === 'second' && ! isset($request['bank_transfer']));
        $this->get(route('checkout.show', [$order->store, $order->openOrder]))->assertRedirect();
        $this->post(route('order.payment.refresh', $order->access_token))->assertRedirect();
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('https://api.sandbox.midtrans.com/v2/qris/test-qr/qr-code', $order->fresh()->payment_details['qr_url']);
    }

    public function test_digital_checkout_stops_at_fifteen_minutes_before_closing(): void
    {
        $this->travelTo(now()->startOfMinute());
        [$url, $data] = $this->checkout();
        $period = OpenOrder::sole();
        $period->update(['closes_at' => now()->addMinutes(15)]);
        $data['payment_method'] = 'qris';
        unset($data['payment_bank']);
        $this->post($url, $data)->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
        Http::assertNothingSent();
    }

    public function test_unpaid_qris_cannot_be_prepared_by_seller_and_wrong_payment_type_never_marks_paid(): void
    {
        [$url, $data, $id] = $this->checkout();
        $data['payment_method'] = 'qris';
        unset($data['payment_bank']);
        Http::fake(['*/v2/*/status' => Http::sequence()->push(['status_code' => '404'], 404)->push($this->payload($id, ['transaction_status' => 'settlement'])), '*/v2/charge' => Http::response($this->payload($id, ['payment_type' => 'qris', 'va_numbers' => []]), 201)]);
        $this->post($url, $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        $this->actingAs($order->store->owner)->patch(route('admin.open-orders.order-status', [$order->store, $order->openOrder, $order]), ['status' => 'preparing', 'expected_status' => 'received'])->assertSessionHasErrors('status');
        $this->post(route('order.payment.refresh', $order->access_token))->assertRedirect();
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertSame('received', $order->fresh()->status);
        $this->assertSame('response_mismatch', $order->fresh()->payment_details['last_error']['kind']);
    }
}
