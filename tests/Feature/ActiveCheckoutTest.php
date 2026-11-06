<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\GuestOrderIdentity;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tests\TestCase;

class ActiveCheckoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function checkout(): array
    {
        $store = Store::factory()->create(['is_listed' => true]);
        $period = $store->openOrders()->create(['name' => 'Session', 'status' => 'published', 'opens_at' => now()->subHour(), 'closes_at' => now()->addDay(),
            'fulfillment' => ['pickup_enabled' => true, 'delivery_enabled' => true, 'latitude' => '0.9', 'longitude' => '109', 'delivery_fee_mode' => 'fixed', 'delivery_fixed_fee' => 1000, 'delivery_min_distance_m' => 100, 'delivery_max_distance_m' => 5000]]);
        $category = $store->catalogCategories()->create(['name' => 'Coffee']);
        $product = $store->catalogProducts()->create(['name' => 'Coffee', 'price' => 10000, 'catalog_category_id' => $category->id]);
        $item = $period->items()->create(['catalog_product_id' => $product->id, 'name' => 'Coffee', 'price' => 10000, 'quota' => null, 'options' => []]);

        return [$store, $period, ['idempotency_key' => (string) Str::uuid(), 'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'pickup', 'payment_method' => 'cash', 'items' => [['id' => $item->id, 'quantity' => 1, 'choices' => []]]]];
    }

    public function test_guest_cannot_checkout_at_another_store_until_previous_order_completed(): void
    {
        [$store, $period, $data] = $this->checkout();
        $key = Str::random(64);
        $this->withCookie(GuestOrderIdentity::COOKIE, $key)->post(route('checkout.store', [$store, $period]), $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        [$other, $next, $nextData] = $this->checkout();
        $this->get(route('checkout.show', [$other, $next]))->assertRedirectToRoute('order.receipt', $order->access_token);
        $url = route('checkout.store', [$other, $next]);
        foreach (['received', 'preparing', 'ready', 'out_for_delivery'] as $status) {
            $order->status = $status;
            $order->save();
            $this->post($url, $nextData)->assertSessionHasErrors('items');
            $this->assertDatabaseCount('rintis_orders', 1);
        }
        $order->payment_status = 'paid';
        $order->save();
        $this->post($url, $nextData)->assertSessionHasErrors('items');
        $order->status = 'completed';
        $order->save();
        $this->post($url, $nextData)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('rintis_orders', 2);
    }

    public function test_account_order_blocks_across_browsers_but_not_another_customer(): void
    {
        [$store, $period, $data] = $this->checkout();
        $buyer = User::factory()->create();
        $this->actingAs($buyer)->withCookie(GuestOrderIdentity::COOKIE, Str::random(64))->post(route('checkout.store', [$store, $period]), $data)->assertSessionHasNoErrors();
        $order = Order::sole();
        [$other, $next, $nextData] = $this->checkout();
        $this->withCookie(GuestOrderIdentity::COOKIE, Str::random(64))->get(route('checkout.show', [$other, $next]))->assertRedirectToRoute('order.receipt', $order->access_token);
        $this->post(route('checkout.store', [$other, $next]), $nextData)->assertSessionHasErrors('items');
        $this->actingAs(User::factory()->create())->get(route('checkout.show', [$other, $next]))->assertOk();
        $this->post(route('checkout.store', [$other, $next]), $nextData)->assertSessionHasNoErrors();
        $this->assertDatabaseCount('rintis_orders', 2);
    }

    public function test_demo_routing_is_rate_limited_and_rejected_in_production(): void
    {
        config(['rintis.routing_url' => 'https://routing.test/route/v1/driving', 'rintis.routing_demo' => true]);
        RateLimiter::clear('rintis-demo-routing');
        Http::preventStrayRequests();
        Http::fake(['routing.test/*' => Http::response(['code' => 'Ok', 'routes' => [['distance' => 1000]]])]);
        [$store, $period] = $this->checkout();
        $url = route('checkout.quote', [$store, $period]);
        $this->postJson($url, ['latitude' => 0.91, 'longitude' => 109.01])->assertOk();
        $this->postJson($url, ['latitude' => 0.91, 'longitude' => 109.01])->assertUnprocessable()->assertJsonValidationErrors('delivery');
        Http::assertSentCount(1);
        $this->withoutMiddleware(PreventRequestForgery::class);
        $this->app->instance('env', 'production');
        $this->postJson($url, ['latitude' => 0.91, 'longitude' => 109.01])->assertUnprocessable()->assertJsonValidationErrors('delivery');
        Http::assertSentCount(1);
    }
}
