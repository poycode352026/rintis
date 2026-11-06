<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompletePickupRequest;
use App\Models\Order;
use App\Models\Store;
use App\Services\CompletePickup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class PickupController extends Controller
{
    public function entry(Request $request): RedirectResponse
    {
        $store = $request->user()->store;

        return $store ? to_route('admin.pickup.show', $store) : to_route('admin.stores.create');
    }

    public function show(Store $store): Response
    {
        Gate::authorize('update', $store);

        return Inertia::render('Admin/Pickup', [
            'storeName' => $store->name, 'lookupUrl' => route('admin.pickup.lookup', $store),
            'completeUrl' => route('admin.pickup.complete', $store), 'backUrl' => route('admin.stores.show', $store),
            'catalogUrl' => route('admin.catalog.index', $store), 'settingsUrl' => route('admin.stores.edit', $store),
        ]);
    }

    public function lookup(CompletePickupRequest $request, Store $store): JsonResponse
    {
        $order = Order::query()->where('store_id', $store->id)->where('method', 'pickup')->where('pickup_code_hash', hash('sha256', $request->validated('code')))->firstOrFail();
        abort_unless(in_array($order->status, ['ready', 'completed'], true), 422, 'This order is not ready for pickup.');

        return response()->json(['order' => $order->receipt()])->header('Cache-Control', 'private, no-store');
    }

    public function complete(CompletePickupRequest $request, Store $store, CompletePickup $complete): JsonResponse
    {
        $cash = $request->validated('cash_received');
        $order = $complete->handle($store, $request->validated('code'), $cash === null ? null : (int) $cash, $request->user(), $request->boolean('keep_change'));

        return response()->json(['order' => $order->receipt()])->header('Cache-Control', 'private, no-store');
    }
}
