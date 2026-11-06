<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ActiveCheckout
{
    public function lock(string $guestKey, ?int $customerId): void
    {
        $keys = [hash('sha256', 'guest:'.$guestKey)];
        if ($customerId !== null) {
            $keys[] = hash('sha256', 'customer:'.$customerId);
        }
        sort($keys);
        foreach ($keys as $key) {
            DB::table('rintis_checkout_guards')->insertOrIgnore(['identity_hash' => $key]);
            DB::table('rintis_checkout_guards')->where('identity_hash', $key)->lockForUpdate()->first();
        }
    }

    public function find(string $guestKey, ?int $customerId): ?Order
    {
        $orders = Order::query()->whereNotIn('status', ['completed', 'expired', 'cancelled'])->where(function (Builder $query) use ($guestKey, $customerId): void {
            $query->where(fn (Builder $guest) => $guest->where('guest_key', $guestKey)->whereNull('customer_id'));
            if ($customerId !== null) {
                $query->orWhere('customer_id', $customerId);
            }
        })->latest('id')->get();
        foreach ($orders as $order) {
            app(ExpireQrisOrder::class)->handle($order);
            if (! in_array($order->fresh()->status, ['expired', 'cancelled'], true)) {
                return $order->fresh();
            }
        }

        return null;
    }
}
