<?php

namespace App\Services;

use App\Models\DeliveryRun;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Throwable;

class PlanDelivery
{
    public function handle(Store $store, array $ids): DeliveryRun
    {
        $orders = $store->orders()->whereIn('id', $ids)->orderBy('id')->get();
        $this->validateOrders($orders, count($ids));
        $origin = ['latitude' => $store->latitude, 'longitude' => $store->longitude];
        $points = [$origin, ...$orders->map(fn (Order $order) => $order->fulfillment)->all()];
        foreach ($points as $point) {
            if (! is_numeric($point['latitude'] ?? null) || ! is_numeric($point['longitude'] ?? null)
                || abs((float) $point['latitude']) > 90 || abs((float) $point['longitude']) > 180) {
                throw ValidationException::withMessages(['orders' => 'Every delivery and the store need a valid map pin.']);
            }
        }
        $url = config('rintis.routing_url');
        if (! $url || ! str_contains($url, '/route/v1/')) {
            throw ValidationException::withMessages(['orders' => 'Configure an OSRM-compatible routing service before planning deliveries.']);
        }
        if (config('rintis.routing_demo') && (! app()->environment(['local', 'testing']) || RateLimiter::hit('rintis-demo-routing', 1) > 1)) {
            throw ValidationException::withMessages(['orders' => 'Demo routing is limited to local testing. Wait one second before retrying.']);
        }
        try {
            $coordinates = implode(';', array_map(fn ($point) => $point['longitude'].','.$point['latitude'], $points));
            $response = Http::acceptJson()->withUserAgent('OneclickStore-Rintis/1.0 ('.config('app.url').')')->connectTimeout(4)->timeout(12)
                ->get(str_replace('/route/v1/', '/table/v1/', rtrim($url, '/')).'/'.$coordinates, ['annotations' => 'distance', 'radiuses' => implode(';', array_fill(0, count($points), '100'))])->throw();
            $matrix = $response->json('distances');
            if ($response->json('code') !== 'Ok' || ! is_array($matrix) || count($matrix) !== count($points)) {
                throw new \RuntimeException;
            }
            $remaining = range(1, count($points) - 1);
            $current = 0;
            $stops = [];
            $total = 0;
            while ($remaining) {
                foreach ($remaining as $index) {
                    if (! is_numeric($matrix[$current][$index] ?? null) || $matrix[$current][$index] < 0) {
                        throw new \RuntimeException;
                    }
                }
                usort($remaining, fn ($a, $b) => $matrix[$current][$a] <=> $matrix[$current][$b] ?: $a <=> $b);
                $next = array_shift($remaining);
                $distance = (int) round($matrix[$current][$next]);
                $stops[] = ['order_id' => $orders[$next - 1]->id, 'distance_m' => $distance];
                $total += $distance;
                $current = $next;
            }
        } catch (Throwable) {
            throw ValidationException::withMessages(['orders' => 'Road distances could not be calculated. Check the pins and retry; no route has been saved.']);
        }

        return DB::transaction(function () use ($store, $ids, $stops, $origin, $total): DeliveryRun {
            Store::query()->whereKey($store->id)->lockForUpdate()->firstOrFail();
            if (DeliveryRun::where('store_id', $store->id)->whereIn('status', ['planned', 'active'])->exists()) {
                throw ValidationException::withMessages(['orders' => 'Finish or clear the current delivery route first.']);
            }
            $this->validateOrders($store->orders()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get(), count($ids));

            return DeliveryRun::create(['store_id' => $store->id, 'stops' => $stops, 'origin' => $origin, 'distance_m' => $total]);
        });
    }

    private function validateOrders($orders, int $count): void
    {
        if ($orders->count() !== $count || $orders->contains(fn (Order $order) => $order->method !== 'delivery'
            || ! in_array($order->status, ['ready', 'out_for_delivery'], true)
            || ($order->payment_method !== 'cash' && $order->payment_status !== 'paid'))) {
            throw ValidationException::withMessages(['orders' => 'Choose ready delivery orders from your store. Transfers must be paid first.']);
        }
    }
}
