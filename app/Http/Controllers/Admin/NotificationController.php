<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\OrderActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $store = $request->user()->store;
        if (! $store) {
            return response()->json(['unread' => 0, 'items' => []])->header('Cache-Control', 'private, no-store');
        }
        Gate::authorize('view', $store);
        $query = $request->user()->notifications()->whereIn('type', [OrderActivity::class, 'App\\Notifications\\Rintis\\OrderActivity'])->where('data->store_id', $store->id);
        $notifications = (clone $query)->latest()->limit(20)->get();
        $orders = $store->orders()->whereIn('id', $notifications->map(fn ($item) => $item->data['order_id']))->get(['id', 'open_order_id', 'status', 'payment_method', 'payment_status', 'method'])->keyBy('id');

        return response()->json(['unread' => (clone $query)->whereNull('read_at')->count(),
            'items' => $notifications->map(function ($notification) use ($orders, $store): array {
                $data = $notification->data;
                $order = $orders->get($data['order_id']);

                return ['id' => $notification->id, 'title' => $data['title'], 'reference' => $data['reference'], 'total' => $data['total'], 'kind' => $data['kind'],
                    'read' => $notification->read_at !== null, 'at' => $notification->created_at->toIso8601String(),
                    'url' => $order ? route('admin.open-orders.orders', ['store' => $store, 'openOrder' => $order->open_order_id, 'view' => match (true) {
                        $order->status === 'completed' => 'completed', $order->status === 'cancelled' => 'cancelled', $order->status === 'expired' => 'expired', $order->payment_method !== 'cash' && $order->payment_status !== 'paid' => 'unpaid', $order->status === 'received' => 'new', $order->method === 'delivery' && in_array($order->status, ['ready', 'out_for_delivery'], true) => 'delivery', default => 'preparing'
                    }, 'focus' => $order->id]) : null];
            })])->header('Cache-Control', 'private, no-store');
    }

    public function read(Request $request): JsonResponse
    {
        $data = $request->validate(['id' => ['nullable', 'uuid']]);
        $store = $request->user()->store;
        abort_unless($store, 404);
        Gate::authorize('update', $store);
        $query = $request->user()->notifications()->whereIn('type', [OrderActivity::class, 'App\\Notifications\\Rintis\\OrderActivity'])->where('data->store_id', $store->id)->whereNull('read_at');
        if (! empty($data['id'])) {
            $query->whereKey($data['id']);
        }
        $query->update(['read_at' => now()]);

        return response()->json(['saved' => true]);
    }
}
