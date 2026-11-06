<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SellerNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function order(?Store $store = null, string $payment = 'bank_transfer'): Order
    {
        $store ??= Store::factory()->create();
        $period = $store->openOrders()->first() ?? $store->openOrders()->create(['name' => 'Session', 'status' => 'closed', 'opens_at' => now()->subDay(), 'closes_at' => now()->subHour(), 'fulfillment' => []]);

        return Order::create(['store_id' => $store->id, 'open_order_id' => $period->id, 'reference' => 'RT-'.Str::upper(Str::random(12)),
            'idempotency_key' => (string) Str::uuid(), 'guest_key' => Str::random(64), 'access_hash' => hash('sha256', $token = Str::random(64)), 'access_token' => $token,
            'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'delivery', 'payment_method' => $payment, 'payment_status' => 'unpaid',
            'items' => [['name' => 'Coffee', 'quantity' => 1, 'unit_price' => 17000, 'line_total' => 17000, 'choices' => [], 'private_costing' => ['secret' => 1000]]],
            'fulfillment' => ['address_line' => 'Private buyer address', 'latitude' => 1, 'longitude' => 109.1], 'subtotal' => 17000, 'delivery_fee' => 3000, 'total' => 20000]);
    }

    public function test_new_order_and_verified_payment_notify_only_owner_once_with_private_feed(): void
    {
        $order = $this->order();
        $owner = $order->store->owner;
        $this->assertSame(1, $owner->notifications()->count());
        $order->update(['payment_status' => 'paid']);
        $order->update(['payment_status' => 'paid']);
        $this->assertSame(2, $owner->notifications()->count());
        $this->actingAs($owner)->getJson(route('admin.notifications.index'))->assertOk()->assertHeader('Cache-Control', 'no-store, private')->assertJsonPath('unread', 2)->assertJsonCount(2, 'items')->assertJsonMissingPath('items.0.phone')->assertJsonMissingPath('items.0.access_token');
        $paid = $owner->notifications()->get()->first(fn ($item) => $item->data['kind'] === 'paid');
        $this->postJson(route('admin.notifications.read'), ['id' => $paid->id])->assertOk();
        $this->assertSame(1, $owner->unreadNotifications()->count());
        $this->postJson(route('admin.notifications.read'))->assertOk();
        $this->assertSame(0, $owner->unreadNotifications()->count());
    }

    public function test_notifications_from_before_the_namespace_change_remain_readable(): void
    {
        $order = $this->order();
        $owner = $order->store->owner;
        $notification = $owner->notifications()->sole();
        $notification->update(['type' => 'App\\Notifications\\Rintis\\OrderActivity']);
        $this->actingAs($owner)->getJson(route('admin.notifications.index'))->assertOk()->assertJsonPath('unread', 1);
        $this->postJson(route('admin.notifications.read'), ['id' => $notification->id])->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_notification_poll_does_not_replace_previous_page_for_action_redirects(): void
    {
        $order = $this->order();
        $page = route('admin.deliveries.index', $order->store);
        $this->actingAs($order->store->owner)
            ->withSession(['_previous' => ['url' => $page]])
            ->getJson(route('admin.notifications.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertSessionHas('_previous.url', $page);
    }

    public function test_other_seller_cannot_read_or_mark_another_sellers_notifications(): void
    {
        $order = $this->order();
        $other = $this->order();
        $notification = $order->store->owner->notifications()->sole();
        $this->getJson(route('admin.notifications.index'))->assertUnauthorized();
        $this->actingAs($other->store->owner)->getJson(route('admin.notifications.index'))->assertOk()->assertJsonPath('unread', 1)->assertJsonPath('items.0.reference', $other->reference);
        $this->postJson(route('admin.notifications.read'), ['id' => $notification->id])->assertOk();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_rolled_back_order_does_not_leave_a_notification(): void
    {
        $store = Store::factory()->create();
        DB::beginTransaction();
        $this->order($store);
        $this->assertSame(1, $store->owner->notifications()->count());
        DB::rollBack();
        $this->assertSame(0, $store->owner->notifications()->count());
        $this->assertSame(0, Order::count());
    }

    public function test_paid_qris_pickup_moves_through_actionable_preparing_and_completed_groups(): void
    {
        $order = $this->order(payment: 'qris');
        $order->openOrder->update(['closes_at' => now()->addHour()]);
        $order->forceFill(['method' => 'pickup'])->save();
        $base = route('admin.open-orders.orders', [$order->store, $order->openOrder]);
        $this->actingAs($order->store->owner);
        foreach ([['received', 'unpaid', 'unpaid'], ['received', 'paid', 'new'], ['preparing', 'paid', 'preparing'], ['ready', 'paid', 'preparing'], ['completed', 'paid', 'completed'], ['expired', 'unpaid', 'expired']] as [$status, $payment, $view]) {
            $order->forceFill(['status' => $status, 'payment_status' => $payment])->save();
            $this->get($base.'?view='.$view)->assertInertia(fn (Assert $page) => $page->has('orders.data', 1)->where('orders.data.0.method', 'pickup')->where('orders.data.0.id', $order->id));
            $url = $this->getJson(route('admin.notifications.index'))->assertOk()->json('items.0.url');
            $this->assertStringContainsString('view='.$view, $url);
        }
    }

    public function test_order_groups_separate_actionable_unpaid_and_delivery_and_link_to_exact_notified_order(): void
    {
        $unpaid = $this->order();
        $store = $unpaid->store;
        $new = $this->order($store, 'cash');
        $delivery = $this->order($store, 'cash');
        $delivery->forceFill(['status' => 'out_for_delivery'])->save();
        $base = route('admin.open-orders.orders', [$store, $unpaid->openOrder]);
        $this->actingAs($store->owner)->get($base)->assertInertia(fn (Assert $page) => $page->where('view', 'new')->has('orders.data', 1)->where('orders.data.0.id', $new->id)->where('viewCounts.unpaid', 1)->where('viewCounts.delivery', 1));
        $this->get($base.'?view=delivery')->assertInertia(fn (Assert $page) => $page->where('orders.data.0.id', $delivery->id)->where('orders.data.0.deliveryManaged', true)->has('selectableOrders', 0));
        $this->get($base.'?view=unpaid')->assertInertia(fn (Assert $page) => $page->where('orders.data.0.id', $unpaid->id)->has('selectableOrders', 0));
        $this->getJson(route('admin.notifications.index'))->assertOk();
        $url = $this->getJson(route('admin.notifications.index'))->json('items.0.url');
        $this->get($url)->assertInertia(fn (Assert $page) => $page->where('focused', true)->has('orders.data', 1));
    }
}
