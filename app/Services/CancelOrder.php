<?php

namespace App\Services;

use App\Models\DeliveryRun;
use App\Models\OpenOrder;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    public function handle(Order $order, string $reason, User $actor): void
    {
        Gate::forUser($actor)->authorize('update', $order->store);
        DB::transaction(function () use ($order, $reason, $actor): void {
            Store::whereKey($order->store_id)->lockForUpdate()->firstOrFail();
            $period = OpenOrder::whereKey($order->open_order_id)->lockForUpdate()->firstOrFail();
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status === 'cancelled') {
                return;
            }
            if ($order->payment_method !== 'cash' || $order->payment_status !== 'unpaid' || ! in_array($order->status, ['received', 'preparing', 'ready'], true)) {
                throw ValidationException::withMessages(['cancel' => 'Only unpaid cash orders that have not been dispatched can be cancelled. Paid orders require a refund process.']);
            }
            $runs = DeliveryRun::where('store_id', $order->store_id)->whereIn('status', ['planned', 'active'])->get();
            if ($runs->contains(fn ($run) => in_array($order->id, array_column($run->stops, 'order_id'), true))) {
                throw ValidationException::withMessages(['cancel' => 'Clear the planned delivery route before cancelling this order. Orders in an active trip cannot be cancelled.']);
            }
            $history = $order->fulfillment_history ?? [['status' => 'received', 'at' => $order->created_at->toIso8601String()]];
            $history[] = ['status' => 'cancelled', 'at' => now()->toIso8601String(), 'actor_id' => $actor->id, 'reason' => $reason];
            $order->status = 'cancelled';
            $order->fulfillment_history = $history;
            $order->save();
            foreach (collect($order->items)->groupBy('open_order_item_id') as $id => $lines) {
                if (! is_numeric($id) || (int) $id < 1) {
                    continue;
                }
                $item = $period->items()->whereKey($id)->lockForUpdate()->first();
                if ($item) {
                    $item->ordered_quantity = max(0, $item->ordered_quantity - $lines->sum('quantity'));
                    $item->save();
                }
            }
        }, 3);
    }
}
