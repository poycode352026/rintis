<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryRequest;
use App\Models\DeliveryRun;
use App\Models\Order;
use App\Models\Store;
use App\Services\PlanDelivery;
use App\Services\UpdateOrderStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class DeliveryController extends Controller
{
    public function entry(Request $request): RedirectResponse
    {
        $store = $request->user()->store;

        return $store ? to_route('admin.deliveries.index', $store) : to_route('admin.stores.create');
    }

    public function index(Request $request, Store $store): Response
    {
        Gate::authorize('view', $store);
        $run = DeliveryRun::where('store_id', $store->id)->whereIn('status', ['planned', 'active'])->latest('id')->first();
        $runOrders = $run ? $store->orders()->whereIn('id', array_column($run->stops, 'order_id'))->get()->keyBy('id') : collect();
        $response = Inertia::render('Admin/Deliveries', [
            'storeName' => $store->name, 'origin' => $store->only('latitude', 'longitude'),
            'orders' => $store->orders()->where('method', 'delivery')->whereNotIn('status', ['completed', 'expired', 'cancelled'])->oldest('id')->paginate(30)->through(fn (Order $order) => $this->details($order)),
            'run' => $run ? ['id' => $run->id, 'status' => $run->status, 'distance_m' => $run->distance_m, 'origin' => $run->origin,
                'stops' => array_map(fn ($stop) => [...$stop, ...$this->details($runOrders[$stop['order_id']])], $run->stops),
                'startUrl' => route('admin.deliveries.start', [$store, $run]), 'clearUrl' => route('admin.deliveries.clear', [$store, $run]),
                'completeUrl' => route('admin.deliveries.complete', [$store, $run])] : null,
            'planUrl' => route('admin.deliveries.plan', $store), 'bulkUrl' => route('admin.deliveries.bulk', $store),
            'backUrl' => route('admin.stores.show', $store), 'mapTilesUrl' => config('rintis.map_tiles_url'),
            'settingsUrl' => route('admin.stores.edit', $store), 'catalogUrl' => route('admin.catalog.index', $store),
        ])->toResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }

    private function details(Order $order): array
    {
        return [...$order->only('id', 'reference', 'customer_name', 'phone', 'status', 'payment_status', 'payment_method', 'total'),
            'location' => $order->fulfillment, 'items' => $order->receipt()['items']];
    }

    public function plan(DeliveryRequest $request, Store $store, PlanDelivery $planner): RedirectResponse
    {
        $planner->handle($store, $request->validated('orders'));

        return back()->with('status', 'Delivery route saved. Review the stop order before starting.');
    }

    public function bulk(DeliveryRequest $request, Store $store, UpdateOrderStatus $update): RedirectResponse
    {
        $data = $request->validated();
        $expected = match ($data['stage'] ?? null) {
            'preparing' => 'received', 'ready' => 'preparing', default => null
        };
        if (! $expected) {
            throw ValidationException::withMessages(['stage' => 'Choose a preparation step.']);
        }
        DB::transaction(function () use ($store, $data, $expected, $request, $update): void {
            $orders = $store->orders()->whereIn('id', $data['orders'])->orderBy('id')->lockForUpdate()->get();
            if ($orders->count() !== count($data['orders']) || $orders->contains(fn ($order) => $order->method !== 'delivery' || $order->status !== $expected)) {
                throw ValidationException::withMessages(['orders' => 'Select delivery orders at the same preparation step. Refresh and retry.']);
            }
            foreach ($orders as $order) {
                $update->handle($order, ['expected_status' => $expected, 'status' => $data['stage']], $request->user());
            }
        });

        return back()->with('status', 'Selected orders updated together.');
    }

    public function start(Request $request, Store $store, DeliveryRun $run, UpdateOrderStatus $update): RedirectResponse
    {
        Gate::authorize('update', $store);
        abort_unless($run->store_id === $store->id, 404);
        DB::transaction(function () use ($store, $run, $request, $update): void {
            $run = DeliveryRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
            if ($run->status === 'active') {
                return;
            }
            abort_unless($run->status === 'planned', 422);
            $orders = $store->orders()->whereIn('id', array_column($run->stops, 'order_id'))->orderBy('id')->lockForUpdate()->get();
            foreach ($orders as $order) {
                if (! in_array($order->status, ['ready', 'out_for_delivery'], true)) {
                    throw ValidationException::withMessages(['orders' => 'A stop has changed. Clear this planned route and create it again.']);
                }
                if ($order->status === 'ready') {
                    $update->handle($order, ['expected_status' => 'ready', 'status' => 'out_for_delivery'], $request->user());
                }
                $order->refresh();
                $history = $order->fulfillment_history ?? [];
                $history[] = ['status' => 'delivery_started', 'at' => now()->toIso8601String(), 'actor_id' => $request->user()->id];
                $order->fulfillment_history = $history;
                $order->save();
            }
            $run->update(['status' => 'active']);
        });

        return back()->with('status', 'Delivery started. Follow the numbered stops.');
    }

    public function clear(Store $store, DeliveryRun $run): RedirectResponse
    {
        Gate::authorize('update', $store);
        abort_unless($run->store_id === $store->id, 404);
        DB::transaction(function () use ($run): void {
            $run = DeliveryRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
            abort_unless($run->status === 'planned', 422);
            $run->update(['status' => 'discarded']);
        });

        return back()->with('status', 'Planned route cleared. Order progress is unchanged.');
    }

    public function complete(Request $request, Store $store, DeliveryRun $run): RedirectResponse
    {
        Gate::authorize('update', $store);
        abort_unless($run->store_id === $store->id, 404);
        $data = $request->validate(['keep_change' => ['sometimes', 'boolean'], 'order_id' => ['required', 'integer'], 'cash_received' => ['nullable', 'integer', 'min:0', 'max:999999999999999']]);
        $data['order_id'] = (int) $data['order_id'];
        DB::transaction(function () use ($store, $run, $data, $request): void {
            $run = DeliveryRun::whereKey($run->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($data['order_id'], array_column($run->stops, 'order_id'), true), 404);
            $orders = $store->orders()->whereIn('id', array_column($run->stops, 'order_id'))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $order = $orders[$data['order_id']];
            if ($order->status === 'completed') {
                return;
            }
            $next = collect($run->stops)->first(fn ($stop) => $orders[$stop['order_id']]->status !== 'completed');
            if ($run->status !== 'active' || $next['order_id'] !== $order->id || $order->status !== 'out_for_delivery') {
                throw ValidationException::withMessages(['order_id' => 'Complete the next delivery stop in sequence.']);
            }
            if ($order->payment_status !== 'paid') {
                if ($order->payment_method !== 'cash' || ($data['cash_received'] ?? -1) < $order->total) {
                    throw ValidationException::withMessages(['cash_received' => 'Collect the full payment before completing delivery.']);
                }
                $order->cash_received = $data['cash_received'];
                $order->cash_change = ! empty($data['keep_change']) ? 0 : $data['cash_received'] - $order->total;
                $order->payment_status = 'paid';
            }
            $history = $order->fulfillment_history ?? [];
            $history[] = ['status' => 'completed', 'at' => now()->toIso8601String(), 'actor_id' => $request->user()->id];
            $order->status = 'completed';
            $order->fulfillment_history = $history;
            $order->save();
            if ($orders->every(fn ($item) => $item->status === 'completed')) {
                $run->update(['status' => 'completed']);
            }
        });

        return back()->with('status', 'Delivery completed. Continue to the next stop.');
    }
}
