<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class ExpireQrisOrder
{
    public function handle(Order $order): bool
    {
        if ($order->payment_method !== 'qris' || $order->payment_status === 'paid' || $order->status !== 'received'
            || $order->openOrder->closes_at->subMinutes(15)->gt(now())) {
            return false;
        }
        // Check the provider first: a payment made on time may reach us after the cutoff.
        app(MidtransPayment::class)->sync($order, force: true);

        return DB::transaction(function () use ($order): bool {
            $locked = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($locked->payment_status === 'paid' || $locked->status !== 'received') {
                return false;
            }
            $details = $locked->payment_details ?? [];
            $details['expired_at'] = now()->toIso8601String();
            $history = $locked->fulfillment_history ?? [['status' => 'received', 'at' => $locked->created_at->toIso8601String()]];
            $history[] = ['status' => 'expired', 'at' => now()->toIso8601String(), 'actor_id' => null];
            $locked->status = 'expired';
            $locked->payment_details = $details;
            $locked->fulfillment_history = $history;
            $locked->save();

            return true;
        }, 3);
    }

    public function forStore(int $storeId): void
    {
        Order::query()->where('store_id', $storeId)->where('payment_method', 'qris')->where('payment_status', '!=', 'paid')->where('status', 'received')
            ->whereHas('openOrder', fn ($query) => $query->where('closes_at', '<=', now()->addMinutes(15)))
            ->eachById(function (Order $order): void {
                $this->handle($order);
            }, 50);
    }
}
