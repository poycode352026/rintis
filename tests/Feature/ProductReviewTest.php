<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\CatalogProduct;
use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function order(): Order
    {
        $product = CatalogProduct::factory()->create(['is_visible' => true]);
        $store = $product->store;
        $store->update(['is_listed' => true]);
        $period = $store->openOrders()->create(['name' => 'Session', 'status' => 'closed', 'opens_at' => now()->subDay(), 'closes_at' => now()->subHour(), 'fulfillment' => ['pickup_enabled' => false, 'delivery_enabled' => true]]);
        $menu = $period->items()->create(['catalog_product_id' => $product->id, 'name' => 'Coffee', 'price' => 10000]);
        $order = Order::create(['store_id' => $store->id, 'open_order_id' => $period->id, 'reference' => 'RT-'.Str::random(12), 'idempotency_key' => (string) Str::uuid(), 'guest_key' => Str::random(64), 'access_hash' => hash('sha256', $token = Str::random(64)), 'access_token' => $token, 'customer_name' => 'Private buyer', 'phone' => '081234567890', 'method' => 'delivery', 'payment_method' => 'cash', 'payment_status' => 'paid', 'items' => [
            ['open_order_item_id' => $menu->id, 'name' => 'Coffee', 'quantity' => 2, 'unit_price' => 10000, 'line_total' => 20000, 'choices' => []],
            ['open_order_item_id' => $menu->id, 'name' => 'Coffee', 'quantity' => 1, 'unit_price' => 10000, 'line_total' => 10000, 'choices' => [['group' => 'Size', 'name' => 'Large']]],
        ], 'fulfillment' => [], 'subtotal' => 30000, 'delivery_fee' => 0, 'total' => 30000]);
        $order->status = 'completed';
        $order->save();

        return $order;
    }

    public function test_guest_rates_product_once_across_variants_and_public_catalog_only_exposes_aggregate(): void
    {
        $order = $this->order();
        $url = route('order.review', $order->access_token);
        $this->post($url, ['line_index' => 0, 'rating' => 5, 'catalog_product_id' => 9999])->assertRedirect(route('order.receipt', $order->access_token));
        $this->post($url, ['line_index' => 0, 'rating' => 1])->assertRedirect();
        $this->post($url, ['line_index' => 1, 'rating' => 3])->assertRedirect();

        $this->assertSame(1, ProductReview::count());
        $this->assertSame(5, ProductReview::where('line_index', 0)->first()->rating);
        $this->get(route('order.receipt', $order->access_token))->assertInertia(fn (Assert $page) => $page->where('order.reviews.0.rating', 5)->where('order.reviewUrl', $url));
        $this->get(route('storefront', $order->store))->assertInertia(fn (Assert $page) => $page->where('products.data.0.rating', 5)->where('products.data.0.ratingCount', 1)->missing('products.data.0.reviews')->missing('products.data.0.customer_name'));
    }

    public function test_reviews_require_completed_paid_order_valid_token_and_purchased_line(): void
    {
        $order = $this->order();
        $url = route('order.review', $order->access_token);
        $this->post(route('order.review', Str::random(64)), ['line_index' => 0, 'rating' => 5])->assertNotFound();
        $this->post($url, ['line_index' => 90, 'rating' => 5])->assertNotFound();
        $this->post($url, ['line_index' => 0, 'rating' => 6])->assertSessionHasErrors('rating');
        $this->post($url, ['line_index' => -1, 'rating' => 5])->assertSessionHasErrors('line_index');
        $order->status = 'out_for_delivery';
        $order->save();
        $this->post($url, ['line_index' => 0, 'rating' => 5])->assertForbidden();
        $this->get(route('order.receipt', $order->access_token))->assertInertia(fn (Assert $page) => $page->where('order.reviewUrl', null));
        $order->status = 'completed';
        $order->payment_status = 'unpaid';
        $order->save();
        $this->post($url, ['line_index' => 0, 'rating' => 5])->assertForbidden();
        $this->assertSame(0, ProductReview::count());
    }

    public function test_receipt_poll_observes_completed_status_and_enables_ratings_without_login(): void
    {
        $order = $this->order();
        $order->status = 'out_for_delivery';
        $order->save();
        $url = route('order.receipt', $order->access_token);
        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('order.status', 'out_for_delivery')->where('order.reviewUrl', null));
        $order->status = 'completed';
        $order->save();

        $this->get($url, ['X-Inertia' => 'true', 'X-Inertia-Version' => app(HandleInertiaRequests::class)->version(Request::create('/')), 'X-Inertia-Partial-Component' => 'Storefront/Receipt', 'X-Inertia-Partial-Data' => 'order,serverNow'])->assertOk()->assertJsonPath('props.order.status', 'completed')->assertJsonPath('props.order.reviewUrl', route('order.review', $order->access_token));
    }

    public function test_batch_ratings_save_together_and_invalid_line_rolls_everything_back(): void
    {
        $order = $this->order();
        $url = route('order.review', $order->access_token);
        $this->post($url, ['reviews' => [['line_index' => 0, 'rating' => 5], ['line_index' => 90, 'rating' => 4]]])->assertNotFound();
        $this->assertSame(0, ProductReview::count());
        $this->post($url, ['reviews' => [['line_index' => 0, 'rating' => 5], ['line_index' => 0, 'rating' => 4]]])->assertSessionHasErrors('reviews.0.line_index');
        $this->post($url, ['reviews' => [['line_index' => 0, 'rating' => 5], ['line_index' => 1, 'rating' => 4]]])->assertRedirect();
        $this->assertSame(1, ProductReview::count());
        $order->openOrder->update(['status' => 'published', 'opens_at' => now()->subHour(), 'closes_at' => now()->addHour()]);
        $this->get(route('open-order', $order->store))->assertOk()->assertInertia(fn (Assert $page) => $page->where('items.0.rating', 5)->where('items.0.ratingCount', 1));
    }
}
