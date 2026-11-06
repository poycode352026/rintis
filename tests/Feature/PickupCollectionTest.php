<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PickupCollectionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function order(string $payment = 'cash', string $paymentStatus = 'unpaid', string $status = 'ready'): Order
    {
        $store = Store::factory()->create(['is_listed' => true]);
        $period = $store->openOrders()->create(['name' => 'Session', 'status' => 'closed', 'opens_at' => now()->subDay(), 'closes_at' => now()->subHour(), 'fulfillment' => []]);

        $order = Order::create(['store_id' => $store->id, 'open_order_id' => $period->id, 'reference' => 'RT-'.Str::upper(Str::random(12)),
            'idempotency_key' => (string) Str::uuid(), 'guest_key' => Str::random(64), 'access_hash' => hash('sha256', $token = Str::random(64)), 'access_token' => $token,
            'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'pickup', 'payment_method' => $payment, 'payment_status' => $paymentStatus,
            'items' => [['name' => 'Coffee', 'quantity' => 1, 'unit_price' => 20620, 'line_total' => 20620, 'choices' => [], 'private_costing' => ['secret' => 1000]]],
            'fulfillment' => ['address_line' => 'Private store address', 'latitude' => '0.9', 'longitude' => '109'], 'subtotal' => 20620, 'delivery_fee' => 0, 'total' => 20620]);
        $order->forceFill(['status' => $status])->save();

        return $order;
    }

    public function test_ready_pickup_qr_is_private_and_cash_collection_is_atomic_and_idempotent(): void
    {
        $order = $this->order();
        $store = $order->store;
        $this->get(route('order.receipt', $order->access_token))->assertOk()->assertInertia(fn (Assert $page) => $page->where('order.pickupPass', $order->pickup_code)->missing('order.pickup_code_hash'));
        $this->assertNotSame($order->pickup_code, $order->getRawOriginal('pickup_code'));
        $this->assertArrayNotHasKey('pickup_code', $order->toArray());
        $this->actingAs($store->owner)->get(route('admin.pickup.show', $store))->assertOk();
        $this->postJson(route('admin.pickup.lookup', $store), ['code' => 'RINTIS-PICKUP:'.$order->pickup_code])->assertOk()->assertJsonPath('order.customer_name', 'Buyer')->assertJsonMissingPath('order.items.0.private_costing')->assertJsonMissingPath('order.access_token');
        $url = route('admin.pickup.complete', $store);
        $this->postJson($url, ['code' => $order->pickup_code, 'cash_received' => 20000])->assertUnprocessable()->assertJsonValidationErrors('cash_received');
        $this->assertSame('ready', $order->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->postJson($url, ['code' => $order->pickup_code, 'cash_received' => 25000])->assertOk()->assertJsonPath('order.status', 'completed')->assertJsonPath('order.cash_change', 4380);
        $collected = $order->fresh();
        $this->assertSame('paid', $collected->payment_status);
        $this->assertSame($store->owner_id, $collected->collected_by);
        $this->assertNotNull($collected->collected_at);
        $this->assertCount(2, $collected->fulfillment_history);
        $this->postJson($url, ['code' => $order->pickup_code, 'cash_received' => 50000])->assertOk()->assertJsonPath('order.cash_received', 25000)->assertJsonPath('order.cash_change', 4380);
        $this->assertSame($collected->getRawOriginal(), $order->fresh()->getRawOriginal());
        $this->post(route('logout'));
        $this->get(route('order.receipt', $order->access_token))->assertOk()->assertInertia(fn (Assert $page) => $page->where('order.pickupPass', null)->where('order.status', 'completed')->where('order.cash_change', 4380));
    }

    public function test_customer_can_leave_change_without_losing_actual_cash_received(): void
    {
        $order = $this->order();
        $order->update(['total' => 22423]);
        $url = route('admin.pickup.complete', $order->store);
        $this->actingAs($order->store->owner)->postJson($url, ['code' => $order->pickup_code, 'cash_received' => 30000, 'keep_change' => true])
            ->assertOk()->assertJsonPath('order.cash_received', 30000)->assertJsonPath('order.cash_change', 0)->assertJsonPath('order.total', 22423);
        $this->postJson($url, ['code' => $order->pickup_code, 'cash_received' => 50000, 'keep_change' => false])->assertOk()->assertJsonPath('order.cash_received', 30000)->assertJsonPath('order.cash_change', 0);
    }

    public function test_prepaid_order_is_collected_without_another_cash_payment(): void
    {
        $order = $this->order('bank_transfer', 'paid');
        $this->actingAs($order->store->owner)->postJson(route('admin.pickup.complete', $order->store), ['code' => implode('-', str_split(strtolower($order->pickup_code), 4))])->assertOk()->assertJsonPath('order.status', 'completed')->assertJsonPath('order.payment_status', 'paid')->assertJsonPath('order.cash_received', null)->assertJsonPath('order.cash_change', null);
    }

    public function test_received_and_unverified_transfer_orders_cannot_be_collected(): void
    {
        $order = $this->order('cash', 'unpaid', 'received');
        $this->get(route('order.receipt', $order->access_token))->assertInertia(fn (Assert $page) => $page->where('order.pickupPass', null)->where('order.pickup', null));
        $this->actingAs($order->store->owner)->postJson(route('admin.pickup.lookup', $order->store), ['code' => $order->pickup_code])->assertUnprocessable();
        $this->postJson(route('admin.pickup.complete', $order->store), ['code' => $order->pickup_code, 'cash_received' => 25000])->assertUnprocessable()->assertJsonValidationErrors('code');
        $order->forceFill(['status' => 'ready', 'payment_method' => 'bank_transfer'])->save();
        $this->postJson(route('admin.pickup.complete', $order->store), ['code' => $order->pickup_code, 'cash_received' => 25000])->assertUnprocessable()->assertJsonValidationErrors('code');
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->collected_at);
        $this->assertNull($order->fresh()->cash_received);
    }

    public function test_only_authorized_store_owner_can_scan_or_collect_and_delivery_is_rejected(): void
    {
        $order = $this->order();
        $store = $order->store;
        $this->get(route('admin.pickup.show', $store))->assertRedirectToRoute('login');
        $this->postJson(route('admin.pickup.lookup', $store), ['code' => $order->pickup_code])->assertUnauthorized();
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->postJson(route('admin.pickup.lookup', $store), ['code' => $order->pickup_code])->assertNotFound();
        $this->postJson(route('admin.pickup.complete', $other), ['code' => $order->pickup_code, 'cash_received' => 25000])->assertNotFound();
        $this->actingAs($store->owner)->postJson(route('admin.pickup.lookup', $store), ['code' => 'wrong'])->assertUnprocessable()->assertJsonValidationErrors('code');
        $order->forceFill(['method' => 'delivery'])->save();
        $this->postJson(route('admin.pickup.complete', $store), ['code' => $order->pickup_code, 'cash_received' => 25000])->assertNotFound();
        $this->assertSame('ready', $order->fresh()->status);
    }

    public function test_cash_amount_validation_does_not_change_order_state(): void
    {
        $order = $this->order();
        $store = $order->store;
        $this->actingAs($store->owner);
        foreach ([null, -1, '20000.50', 1000000000000000] as $cash) {
            $this->postJson(route('admin.pickup.complete', $store), ['code' => $order->pickup_code, 'cash_received' => $cash])->assertUnprocessable()->assertJsonValidationErrors('cash_received');
        }
        $this->assertSame('ready', $order->fresh()->status);
        $this->assertSame('unpaid', $order->fresh()->payment_status);
        $this->assertNull($order->fresh()->collected_at);
    }
}
