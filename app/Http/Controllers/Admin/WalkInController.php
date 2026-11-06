<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\WalkInRequest;
use App\Models\OpenOrder;
use App\Models\Store;
use App\Services\MidtransPayment;
use App\Services\PlaceOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class WalkInController extends Controller
{
    public function create(Store $store, OpenOrder $openOrder): Response
    {
        Gate::authorize('update', $store);
        abort_unless($openOrder->displayStatus() === 'open', 404);

        return Inertia::render('Admin/OpenOrders/WalkIn', ['catalogUrl' => route('admin.catalog.index', $store), 'settingsUrl' => route('admin.stores.edit', $store), 'storeName' => $store->name, 'items' => $openOrder->items()->get()->map(fn ($item) => $item->publicDetails()), 'checkoutKey' => (string) Str::uuid(), 'cash' => $store->cash_enabled, 'qris' => $store->bank_transfer_enabled && app(MidtransPayment::class)->available($store) && $openOrder->closes_at->subMinutes(15)->gt(now()), 'submitUrl' => route('admin.walk-in.store', [$store, $openOrder]), 'backUrl' => route('admin.open-orders.orders', [$store, $openOrder])]);
    }

    public function store(WalkInRequest $request, Store $store, OpenOrder $openOrder, PlaceOrder $place): RedirectResponse
    {
        $data = $request->validated();
        $data['customer_name'] = ($data['customer_name'] ?? null) ?: 'Walk-in customer';
        $data['phone'] = $data['phone'] ?? '';
        $guestKey = hash('sha256', 'walk-in:'.$request->user()->id.':'.$data['idempotency_key']);
        $order = DB::transaction(function () use ($place, $openOrder, $data, $guestKey, $request) {
            $order = $place->handle($openOrder, $data, $guestKey, null);
            if (! $order->fulfillment_history) {
                $order->fulfillment_history = [['status' => 'received', 'at' => $order->created_at->toIso8601String(), 'source' => 'walk_in', 'by' => $request->user()->id]];
                $order->save();
            }
            if ($data['payment_method'] === 'cash' && $order->payment_status !== 'paid') {
                if ($data['cash_received'] < $order->total) {
                    throw ValidationException::withMessages(['cash_received' => 'Cash received must cover the order total.']);
                }
                $order->payment_status = 'paid';
                $order->cash_received = $data['cash_received'];
                $order->cash_change = ! empty($data['keep_change']) ? 0 : $data['cash_received'] - $order->total;
                $order->save();
            }

            return $order;
        }, 3);
        app(MidtransPayment::class)->sync($order, create: true);

        return to_route('order.receipt', $order->access_token);
    }
}
