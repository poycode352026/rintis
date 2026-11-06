<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IncomeTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function order(Store $store, string $method = 'cash', string $status = 'completed', string $payment = 'paid', ?int $cash = null, ?int $change = null): Order
    {
        $period = $store->openOrders()->first() ?? $store->openOrders()->create(['name' => 'Session', 'status' => 'closed', 'opens_at' => now()->subDay(), 'closes_at' => now()->subHour(), 'fulfillment' => []]);
        $token = Str::random(64);
        $order = Order::create(['store_id' => $store->id, 'open_order_id' => $period->id, 'reference' => 'RT-'.Str::upper(Str::random(12)), 'idempotency_key' => (string) Str::uuid(), 'guest_key' => Str::random(64), 'access_hash' => hash('sha256', $token), 'access_token' => $token, 'customer_name' => 'Buyer', 'phone' => '081234567890', 'method' => 'delivery', 'payment_method' => $method, 'payment_status' => $payment, 'items' => [], 'fulfillment' => ['address_line' => 'Private address'], 'subtotal' => 8000, 'delivery_fee' => 2000, 'total' => 10000]);
        $order->forceFill(['status' => $status, 'cash_received' => $cash, 'cash_change' => $change])->save();

        return $order;
    }

    public function test_income_separates_sales_payments_change_extra_cash_and_pending_orders(): void
    {
        $this->travelTo(now()->setTimezone('Asia/Jakarta')->setDate(2026, 11, 5)->setTime(12, 0));
        $store = Store::factory()->create();
        $this->order($store, cash: 15000, change: 5000);
        $this->order($store, cash: 15000, change: 0);
        $this->order($store, 'qris');
        $this->order($store, 'qris', 'received');
        $this->order($store, 'cash', 'received', 'unpaid');
        $this->order($store, 'qris', 'cancelled');
        $this->order($store, 'qris', 'expired');
        $this->order(Store::factory()->create());
        $this->actingAs($store->owner)->get(route('admin.income.index', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Admin/Income')
            ->where('summary.productSales', 24000)->where('summary.deliverySales', 6000)->where('summary.completedOrders', 3)
            ->where('summary.trend.2026-11-05.received', 45000)->where('summary.trend.2026-11-05.sales', 30000)->where('summary.received', 45000)->where('summary.cash', 25000)->where('summary.qris', 20000)->where('summary.bank_transfer', 0)
            ->where('summary.changeReturned', 5000)->where('summary.extraCash', 5000)->where('summary.paidInProgress', 10000)
            ->where('orders.total', 7)->missing('orders.data.0.access_token')->missing('orders.data.0.phone')->missing('orders.data.0.fulfillment')->missing('orders.data.0.items'));
        $this->get(route('admin.income.index', [$store, 'payment' => 'qris']))->assertInertia(fn (Assert $page) => $page->where('summary.trend.2026-11-05.received', 20000)->where('summary.received', 20000)->where('summary.cash', 0)->where('orders.total', 4));
    }

    public function test_summary_covers_all_pages_and_links_back_to_the_sellers_order(): void
    {
        $store = Store::factory()->create();
        for ($index = 0; $index < 21; $index++) {
            $order = $this->order($store);
        }
        $this->actingAs($store->owner)->get(route('admin.income.index', $store))->assertOk()->assertInertia(fn (Assert $page) => $page->has('orders.data', 20)->where('orders.total', 21)->where('summary.received', 210000)->where('summary.productSales', 168000)->where('orders.data.0.url', route('admin.open-orders.orders', [$store, $order->open_order_id, 'view' => 'all', 'focus' => $order->id])));
    }

    public function test_date_filters_use_wib_boundaries_and_reject_invalid_ranges(): void
    {
        $store = Store::factory()->create();
        $this->order($store)->forceFill(['created_at' => '2026-11-04 16:59:59'])->save();
        $this->order($store)->forceFill(['created_at' => '2026-11-04 17:00:00'])->save();
        $this->order($store)->forceFill(['created_at' => '2026-11-05 16:59:59'])->save();
        $this->order($store)->forceFill(['created_at' => '2026-11-05 17:00:00'])->save();
        $url = route('admin.income.index', $store);
        $this->actingAs($store->owner)->get($url.'?from=2026-11-05&to=2026-11-05')->assertInertia(fn (Assert $page) => $page->where('summary.trend.2026-11-05.received', 20000)->where('orders.total', 2)->where('summary.received', 20000));
        $this->get($url.'?from=2026-11-05&to=2026-11-04')->assertSessionHasErrors('to');
        $this->get($url.'?from=2020-01-01&to=2026-11-05')->assertSessionHasErrors('to');
        $this->get($url.'?payment=other')->assertSessionHasErrors('payment');
    }

    public function test_income_requires_login_and_store_ownership_and_supports_empty_data(): void
    {
        $store = Store::factory()->create();
        $url = route('admin.income.index', $store);
        $this->get($url)->assertRedirectToRoute('login');
        $other = Store::factory()->create();
        $this->actingAs($other->owner)->get($url)->assertNotFound();
        $this->actingAs($store->owner)->get(route('admin.income.entry'))->assertRedirect($url);
        $this->get($url)->assertOk()->assertInertia(fn (Assert $page) => $page->where('summary.received', 0)->where('orders.total', 0));
    }
}
