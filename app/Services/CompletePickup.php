<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CompletePickup
{
    public function handle(Store $store, string $code, ?int $cashReceived, User $actor, bool $keepChange = false): Order
    {
        Gate::forUser($actor)->authorize('update', $store);

        return DB::transaction(function () use ($store, $code, $cashReceived, $actor, $keepChange): Order {
            $order = Order::query()->where('store_id', $store->id)->where('method', 'pickup')->where('pickup_code_hash', hash('sha256', $code))->lockForUpdate()->firstOrFail();
            if ($order->status === 'completed' && $order->collected_at !== null) {
                return $order;
            }
            if ($order->status !== 'ready') {
                throw ValidationException::withMessages(['code' => 'This order is not ready for pickup.']);
            }
            if ($order->payment_status !== 'paid') {
                if ($order->payment_method !== 'cash' || $order->payment_status !== 'unpaid') {
                    throw ValidationException::withMessages(['code' => 'Verify the bank payment before handing over this order.']);
                }
                if ($cashReceived === null || $cashReceived < $order->total) {
                    throw ValidationException::withMessages(['cash_received' => 'Enter cash received covering the full order total.']);
                }
                $order->payment_status = 'paid';
                $order->cash_received = $cashReceived;
                $order->cash_change = $keepChange ? 0 : $cashReceived - $order->total;
            }
            $history = $order->fulfillment_history ?? [['status' => 'received', 'at' => $order->created_at->toIso8601String(), 'actor_id' => null]];
            $history[] = ['status' => 'completed', 'at' => now()->toIso8601String(), 'actor_id' => $actor->id];
            $order->fulfillment_history = $history;
            $order->status = 'completed';
            $order->collected_at = now();
            $order->collected_by = $actor->id;
            $order->save();

            return $order;
        });
    }
}
