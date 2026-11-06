<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\GuestOrderIdentity;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OrderHistoryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function order(string $guestKey, ?User $customer = null): Order
    {
        $store = Store::factory()->create(['is_listed' => true]);
        $period = $store->openOrders()->create(['name' => 'Session', 'status' => 'published', 'opens_at' => now()->subHour(), 'closes_at' => now()->addDay(), 'fulfillment' => ['pickup_enabled' => true, 'delivery_enabled' => false]]);

        return Order::create(['customer_id' => $customer?->id, 'store_id' => $store->id, 'open_order_id' => $period->id, 'reference' => 'RT-'.Str::upper(Str::random(12)),
            'idempotency_key' => (string) Str::uuid(), 'guest_key' => $guestKey, 'access_hash' => hash('sha256', $token = Str::random(64)), 'access_token' => $token,
            'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'pickup', 'payment_method' => 'cash', 'payment_status' => 'unpaid',
            'items' => [['name' => 'Coffee', 'quantity' => 2, 'unit_price' => 10000, 'line_total' => 20000, 'choices' => [], 'private_costing' => ['secret' => 1000]]],
            'fulfillment' => ['address_line' => 'Private store address', 'latitude' => '0.9', 'longitude' => '109'], 'subtotal' => 20000, 'delivery_fee' => 0, 'total' => 20000]);
    }

    public function test_guest_history_only_lists_this_browser_orders_without_private_details(): void
    {
        $key = Str::random(64);
        $own = $this->order($key);
        $this->order(Str::random(64));
        $this->order($key, User::factory()->create());
        $response = $this->withSession(['rintis.guest_key' => $key])->get(route('orders.history').'?customer_id=1&phone=081234567890');
        $response->assertOk()->assertHeader('Referrer-Policy', 'no-referrer')->assertInertia(fn (Assert $page) => $page
            ->where('orders.total', 1)->where('orders.data.0.reference', $own->reference)->where('orders.data.0.storeName', $own->store->name)
            ->where('orders.data.0.url', route('order.receipt', $own->access_token))->where('signedIn', false)
            ->missing('orders.data.0.phone')->missing('orders.data.0.fulfillment')->missing('orders.data.0.pickup_code')->missing('orders.data.0.items.0.private_costing'));
        $cookie = $response->getCookie(GuestOrderIdentity::COOKIE);
        $this->assertSame($key, $cookie->getValue());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertGreaterThan(now()->addDays(29)->timestamp, $cookie->getExpiresTime());
        $this->assertNotSame($key, $response->getCookie(GuestOrderIdentity::COOKIE, false)->getValue());
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_guest_cookie_restores_history_after_session_is_lost_but_new_browser_cannot_access_it(): void
    {
        $key = Str::random(64);
        $this->order($key);
        $this->get(route('orders.history'))->assertInertia(fn (Assert $page) => $page->where('orders.total', 0));
        $this->flushSession();
        $this->withCookie(GuestOrderIdentity::COOKIE, $key)->get(route('orders.history'))->assertOk()->assertInertia(fn (Assert $page) => $page->where('orders.total', 1));
        $this->flushSession();
        $this->withCookie(GuestOrderIdentity::COOKIE, Str::random(64))->get(route('orders.history'))->assertInertia(fn (Assert $page) => $page->where('orders.total', 0));
    }

    public function test_signed_in_customer_sees_account_orders_across_browsers_and_unclaimed_local_guest_orders_only(): void
    {
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $key = Str::random(64);
        $this->order(Str::random(64), $customer);
        $this->order($key);
        $this->order($key, $other);
        $this->order(Str::random(64));
        $this->actingAs($customer)->withCookie(GuestOrderIdentity::COOKIE, $key)->get(route('orders.history'))->assertInertia(fn (Assert $page) => $page->where('orders.total', 2)->where('signedIn', true));
        $this->withCookie(GuestOrderIdentity::COOKIE, Str::random(64))->get(route('orders.history'))->assertInertia(fn (Assert $page) => $page->where('orders.total', 1));
    }

    public function test_history_paginates_and_visiting_a_bearer_receipt_does_not_import_someone_else_history(): void
    {
        $key = Str::random(64);
        $first = $this->order($key);
        for ($i = 1; $i <= 12; $i++) {
            $copy = $first->replicate(['reference', 'idempotency_key', 'access_hash', 'access_token', 'pickup_code', 'pickup_code_hash']);
            $copy->reference = 'RT-'.Str::upper(Str::random(12));
            $copy->idempotency_key = (string) Str::uuid();
            $copy->access_token = $token = Str::random(64);
            $copy->access_hash = hash('sha256', $token);
            $copy->save();
        }
        $this->withCookie(GuestOrderIdentity::COOKIE, $key)->get(route('orders.history'))->assertInertia(fn (Assert $page) => $page->where('orders.total', 13)->has('orders.data', 12));
        $this->get(route('orders.history').'?page=2')->assertInertia(fn (Assert $page) => $page->has('orders.data', 1)->where('orders.data.0.reference', $first->reference));
        $this->flushSession();
        $this->withCookie(GuestOrderIdentity::COOKIE, Str::random(64))->get(route('order.receipt', $first->access_token))->assertOk();
        $this->get(route('orders.history'))->assertInertia(fn (Assert $page) => $page->where('orders.total', 0));
    }

    public function test_checkout_links_orders_to_authenticated_customer_and_blocks_cross_account_idempotency_replay(): void
    {
        $key = Str::random(64);
        $customer = User::factory()->create();
        $other = User::factory()->create();
        $seed = $this->order(Str::random(64));
        $store = $seed->store;
        $period = $seed->openOrder;
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $product = $store->catalogProducts()->create(['name' => 'Coffee', 'price' => 10000, 'catalog_category_id' => $category->id]);
        $item = $period->items()->create(['catalog_product_id' => $product->id, 'name' => 'Coffee', 'price' => 10000, 'quota' => null, 'options' => []]);
        $data = ['customer_id' => $other->id, 'idempotency_key' => (string) Str::uuid(), 'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'pickup', 'payment_method' => 'cash', 'items' => [['id' => $item->id, 'quantity' => 1, 'choices' => []]]];
        $url = route('checkout.store', [$store, $period]);
        $this->actingAs($customer)->withCookie(GuestOrderIdentity::COOKIE, $key)->post($url, $data)->assertSessionHasNoErrors();
        $placed = Order::query()->where('idempotency_key', $data['idempotency_key'])->sole();
        $this->assertSame($customer->id, $placed->customer_id);
        $this->get(route('orders.history'))->assertInertia(fn (Assert $page) => $page->where('orders.total', 1)->where('orders.data.0.reference', $placed->reference));
        $this->actingAs($other)->post($url, $data)->assertSessionHasErrors('items');
        $this->get(route('orders.history'))->assertInertia(fn (Assert $page) => $page->where('orders.total', 0));
        $this->assertSame($customer->id, $placed->fresh()->customer_id);
    }
}
