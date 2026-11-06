<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderStatus
{
    public function handle(Order $order, array $data, User $actor): void
    {
        DB::transaction(function () use ($order, $data, $actor): void {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status === $data['status']) {
                return;
            }
            if ($order->status !== $data['expected_status']) {
                throw ValidationException::withMessages(['status' => 'This order has changed. Refresh and review its current progress.']);
            }
            if ($order->nextStatus() !== $data['status']) {
                throw ValidationException::withMessages(['status' => 'Follow the order steps in sequence.']);
            }
            if ($order->payment_method !== 'cash' && $order->payment_status !== 'paid') {
                throw ValidationException::withMessages(['status' => 'Bank transfer must be verified before preparation. Payment verification is not available yet.']);
            }
            if ($data['status'] === 'out_for_delivery' && ($order->fulfillment['delivery_service'] ?? null) === 'hemat' && ! empty($order->fulfillment['delivery_starts_at']) && now()->lt(CarbonImmutable::parse($order->fulfillment['delivery_starts_at']))) {
                throw ValidationException::withMessages(['status' => 'Hemat delivery starts 15 minutes after ordering closes. Please wait until its scheduled start.']);
            }
            $history = $order->fulfillment_history ?? [['status' => 'received', 'at' => $order->created_at->toIso8601String(), 'actor_id' => null]];
            $history[] = ['status' => $data['status'], 'at' => now()->toIso8601String(), 'actor_id' => $actor->id];
            $order->status = $data['status'];
            $order->fulfillment_history = $history;
            $order->save();
        });
    }
}
