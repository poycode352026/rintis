<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkOrderStatusRequest;
use App\Http\Requests\OpenOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\DeliveryRun;
use App\Models\OpenOrder;
use App\Models\Order;
use App\Models\Store;
use App\Services\CancelOrder;
use App\Services\ExpireQrisOrder;
use App\Services\SaveOpenOrder;
use App\Services\UpdateOrderStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OpenOrderController extends Controller
{
    public function entry(Request $request): RedirectResponse
    {
        $store = $request->user()->store;

        return $store ? to_route('admin.open-orders.index', $store) : to_route('admin.stores.create');
    }

    public function index(Store $store): Response
    {
        Gate::authorize('view', $store);
        app(ExpireQrisOrder::class)->forStore($store->id);

        return Inertia::render('Admin/OpenOrders/Index', [
            ...$this->navigation($store), 'store' => $store->only('name'), 'weeklyUrl' => route('admin.weekly.edit', $store), 'createUrl' => route('admin.open-orders.create', $store), 'sessionBlocked' => $store->openOrders()->where(fn ($query) => $query->where(fn ($active) => $active->where('status', 'published')->where('closes_at', '>', now()))->orWhereHas('orders', fn ($orders) => $orders->whereNotIn('status', ['completed', 'expired', 'cancelled'])))->exists(),
            'periods' => $store->openOrders()->withCount(['items', 'orders'])->latest('id')->paginate(12)->through(fn (OpenOrder $period): array => [
                ...$period->publicSummary(), 'itemCount' => $period->items_count, 'orderCount' => $period->orders_count, 'ordersUrl' => route('admin.open-orders.orders', [$store, $period]),
                'editUrl' => $period->status === 'draft' ? route('admin.open-orders.edit', [$store, $period]) : null,
                'closeUrl' => $period->status === 'published' && $period->closes_at->gt(now()) ? route('admin.open-orders.close', [$store, $period]) : null,
                'cancelUrl' => $period->orders_count === 0 && in_array($period->status, ['draft', 'published'], true) && $period->closes_at->gt(now()) ? route('admin.open-orders.cancel', [$store, $period]) : null,
            ]), 'timezone' => config('rintis.timezone'),
        ]);
    }

    public function orders(Request $request, Store $store, OpenOrder $openOrder): Response
    {
        Gate::authorize('view', $store);
        app(ExpireQrisOrder::class)->forStore($store->id);

        $data = $request->validate(['view' => ['nullable', Rule::in(['new', 'preparing', 'unpaid', 'delivery', 'completed', 'expired', 'cancelled', 'all'])], 'focus' => ['nullable', 'integer', 'min:1']]);
        $view = $data['view'] ?? 'new';
        $counts = [];
        foreach (['new', 'preparing', 'unpaid', 'delivery', 'completed', 'expired', 'cancelled', 'all'] as $key) {
            $counts[$key] = $this->filterOrders($openOrder->orders()->getQuery(), $key)->count();
        }
        $query = $this->filterOrders($openOrder->orders()->getQuery(), $view)->when($data['focus'] ?? null, fn ($query, $id) => $query->whereKey($id));
        $run = DeliveryRun::where('store_id', $store->id)->whereIn('status', ['planned', 'active'])->first();
        $inTrip = $run ? array_column($run->stops, 'order_id') : [];

        return Inertia::render('Admin/OpenOrders/Orders', [
            ...$this->navigation($store), 'periodName' => $openOrder->name, 'walkInUrl' => $openOrder->displayStatus() === 'open' ? route('admin.walk-in.create', [$store, $openOrder]) : null,
            'period' => ['status' => $openOrder->displayStatus(), 'opensAt' => $openOrder->opens_at->toIso8601String(), 'closesAt' => $openOrder->closes_at->toIso8601String()], 'paymentDeadline' => $openOrder->closes_at->subMinutes(15)->toIso8601String(), 'serverNow' => now()->toIso8601String(),
            'view' => $view, 'viewCounts' => $counts, 'focused' => isset($data['focus']),
            'ordersUrl' => route('admin.open-orders.orders', [$store, $openOrder]),
            'deliveryUrl' => $counts['delivery'] > 0 ? route('admin.deliveries.index', $store) : null,
            'bulkStatusUrl' => route('admin.open-orders.bulk-status', [$store, $openOrder]),
            'backUrl' => route('admin.open-orders.index', $store),
            'selectableOrders' => (clone $query)->where(fn ($query) => $query->whereIn('status', ['received', 'preparing'])->orWhere(fn ($delivery) => $delivery->where('status', 'ready')->where('method', 'delivery')))->where(fn ($query) => $query->where('payment_method', 'cash')->orWhere('payment_status', 'paid'))->orderBy('id')->get(['id', 'status', 'method']),
            'orders' => $query->when($view === 'all', fn ($query) => $query->latest('id'), fn ($query) => $query->oldest('id'))->paginate(20)->withQueryString()->through(fn (Order $order): array => [
                ...$order->receipt(), 'cancelUrl' => $order->payment_method === 'cash' && $order->payment_status === 'unpaid' && in_array($order->status, ['received', 'preparing', 'ready'], true) ? route('admin.open-orders.order-cancel', [$store, $openOrder, $order]) : null, 'id' => $order->id, 'placedAt' => $order->created_at->toIso8601String(),
                'deliveryManaged' => $order->method === 'delivery' && in_array($order->status, ['ready', 'out_for_delivery'], true),
                'inDeliveryTrip' => in_array($order->id, $inTrip, true),
                'deliveryUrl' => $order->method === 'delivery' && in_array($order->status, ['ready', 'out_for_delivery'], true) ? route('admin.deliveries.index', $store) : null,
                'statusUrl' => route('admin.open-orders.order-status', [$store, $openOrder, $order]), 'nextStatus' => $order->nextStatus(),
            ]),
        ]);
    }

    public function cancelOrder(Request $request, Store $store, OpenOrder $openOrder, Order $order, CancelOrder $cancel): RedirectResponse
    {
        Gate::authorize('update', $store);
        $data = $request->validate(['reason' => ['required', 'string', 'max:500']]);
        $cancel->handle($order, $data['reason'], $request->user());

        return back()->with('status', 'Order cancelled.');
    }

    private function filterOrders(Builder $query, string $view): Builder
    {
        return match ($view) {
            'new' => $query->where('status', 'received')->where(fn ($query) => $query->where('payment_method', 'cash')->orWhere('payment_status', 'paid')),
            'preparing' => $query->where(fn ($query) => $query->where('status', 'preparing')->orWhere(fn ($query) => $query->where('status', 'ready')->where('method', 'pickup'))),
            'unpaid' => $query->where('payment_method', '!=', 'cash')->where('payment_status', '!=', 'paid')->whereNotIn('status', ['completed', 'expired', 'cancelled']),
            'delivery' => $query->where('method', 'delivery')->whereIn('status', ['ready', 'out_for_delivery']),
            'completed' => $query->where('status', 'completed'),
            'expired' => $query->where('status', 'expired'),
            'cancelled' => $query->where('status', 'cancelled'),
            default => $query,
        };
    }

    public function bulkStatus(BulkOrderStatusRequest $request, Store $store, OpenOrder $openOrder, UpdateOrderStatus $update): RedirectResponse
    {
        $data = $request->validated();
        $expected = match ($data['status']) {
            'preparing' => 'received', 'ready' => 'preparing', 'out_for_delivery' => 'ready'
        };
        DB::transaction(function () use ($data, $expected, $openOrder, $update, $request): void {
            $orders = $openOrder->orders()->whereIn('id', $data['orders'])->orderBy('id')->lockForUpdate()->get();
            if ($orders->count() !== count($data['orders']) || $orders->contains(fn ($order) => $order->status !== $expected || ($data['status'] === 'out_for_delivery' && $order->method !== 'delivery'))) {
                throw ValidationException::withMessages(['orders' => 'Select orders at the same stage from this session. Refresh and retry.']);
            }
            foreach ($orders as $order) {
                $update->handle($order, ['expected_status' => $expected, 'status' => $data['status']], $request->user());
            }
        });

        return back()->with('status', 'Selected orders updated together.');
    }

    public function updateOrderStatus(UpdateOrderStatusRequest $request, Store $store, OpenOrder $openOrder, Order $order, UpdateOrderStatus $update): RedirectResponse
    {
        $update->handle($order, $request->validated(), $request->user());

        return back()->with('status', 'Order progress updated.');
    }

    public function create(Store $store): Response
    {
        Gate::authorize('update', $store);

        return $this->form($store);
    }

    public function edit(Store $store, OpenOrder $openOrder): Response
    {
        Gate::authorize('update', $store);
        abort_unless($openOrder->status === 'draft', 422, 'Only draft sessions can be edited.');

        return $this->form($store, $openOrder);
    }

    private function form(Store $store, ?OpenOrder $period = null): Response
    {
        return Inertia::render('Admin/OpenOrders/Form', [
            ...$this->navigation($store), 'store' => $store->only('name'),
            'period' => $period ? [...$period->only('name'), ...array_intersect_key($period->fulfillment, array_flip(['delivery_fee_mode', 'delivery_fixed_fee', 'delivery_rate_per_km'])), 'opens_at' => $period->opens_at->setTimezone(config('rintis.timezone'))->format('Y-m-d\TH:i'),
                'closes_at' => $period->closes_at->setTimezone(config('rintis.timezone'))->format('Y-m-d\TH:i'),
                'priority_enabled' => $period->fulfillment['delivery_options']['priority']['enabled'] ?? false, 'priority_fee' => $period->fulfillment['delivery_options']['priority']['fee'] ?? 0, 'priority_minutes' => $period->fulfillment['delivery_options']['priority']['minutes'] ?? 30,
                'hemat_enabled' => $period->fulfillment['delivery_options']['hemat']['enabled'] ?? false, 'hemat_fee' => $period->fulfillment['delivery_options']['hemat']['fee'] ?? 0, 'hemat_minutes' => $period->fulfillment['delivery_options']['hemat']['minutes'] ?? 60,
                'delivery_starts_at' => ! empty($period->fulfillment['delivery_starts_at']) ? CarbonImmutable::parse($period->fulfillment['delivery_starts_at'])->setTimezone(config('rintis.timezone'))->format('Y-m-d\TH:i') : null,
                'delivery_ends_at' => ! empty($period->fulfillment['delivery_ends_at']) ? CarbonImmutable::parse($period->fulfillment['delivery_ends_at'])->setTimezone(config('rintis.timezone'))->format('Y-m-d\TH:i') : null,
                'pickup_enabled' => $period->fulfillment['pickup_enabled'], 'delivery_enabled' => $period->fulfillment['delivery_enabled'],
                'items' => $period->items()->get(['catalog_product_id', 'price', 'quota'])] : null,
            'products' => $store->catalogProducts()->with('catalogCategory')->orderBy('name')->get()->map(fn ($product): array => [...$product->only('id', 'name', 'price'), 'category' => $product->catalogCategory->name]),
            'methods' => $store->only('pickup_enabled', 'delivery_enabled', 'delivery_options', 'delivery_fee_mode', 'delivery_fixed_fee', 'delivery_rate_per_km'),
            'submitUrl' => $period ? route('admin.open-orders.update', [$store, $period]) : route('admin.open-orders.store', $store),
            'backUrl' => route('admin.open-orders.index', $store), 'timezone' => config('rintis.timezone'),
        ]);
    }

    public function store(OpenOrderRequest $request, Store $store, SaveOpenOrder $save): RedirectResponse
    {
        $save->handle($store, $request->validated());

        return to_route('admin.open-orders.index', $store)->with('status', 'Open-order session saved.');
    }

    public function update(OpenOrderRequest $request, Store $store, OpenOrder $openOrder, SaveOpenOrder $save): RedirectResponse
    {
        $save->handle($store, $request->validated(), $openOrder);

        return to_route('admin.open-orders.index', $store)->with('status', 'Open-order session saved.');
    }

    public function close(Store $store, OpenOrder $openOrder): RedirectResponse
    {
        return $this->transition($store, $openOrder, 'closed');
    }

    public function cancel(Store $store, OpenOrder $openOrder): RedirectResponse
    {
        return $this->transition($store, $openOrder, 'cancelled');
    }

    private function transition(Store $store, OpenOrder $period, string $status): RedirectResponse
    {
        Gate::authorize('update', $store);
        DB::transaction(function () use ($store, $period, $status): void {
            Store::query()->whereKey($store->id)->lockForUpdate()->firstOrFail();
            $period = $store->openOrders()->whereKey($period->id)->lockForUpdate()->firstOrFail();
            if ($status === 'cancelled' && $period->orders()->exists()) {
                throw ValidationException::withMessages(['session' => 'This session already has customer orders and cannot be cancelled. Use Stop orders to stop new orders; existing orders and payments remain unchanged.']);
            }
            if ($period->status === $status) {
                return;
            }
            if (($status === 'closed' && $period->status !== 'published') || ($status === 'cancelled' && ! in_array($period->status, ['draft', 'published'], true))) {
                throw ValidationException::withMessages(['session' => 'This session cannot make that change.']);
            }
            $period->update(['status' => $status, 'closed_at' => now()]);
        });

        return to_route('admin.open-orders.index', $store)->with('status', $status === 'closed' ? 'New orders stopped. Existing orders and payments are unchanged.' : 'Open-order session cancelled.');
    }

    private function navigation(Store $store): array
    {
        return ['settingsUrl' => route('admin.stores.edit', $store), 'catalogUrl' => route('admin.catalog.index', $store),
            'overviewUrl' => route('admin.stores.show', $store), 'publicUrl' => route('open-order', $store)];
    }
}
