<?php

namespace App\Services;

use App\Models\OpenOrder;
use App\Models\Store;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveOpenOrder
{
    public function handle(Store $store, array $data, ?OpenOrder $period = null): OpenOrder
    {
        app(ExpireQrisOrder::class)->forStore($store->id);

        return DB::transaction(function () use ($store, $data, $period): OpenOrder {
            $store = Store::query()->whereKey($store->id)->lockForUpdate()->firstOrFail();
            if ($period) {
                $period = $store->openOrders()->whereKey($period->id)->lockForUpdate()->firstOrFail();
                if ($period->status !== 'draft') {
                    throw ValidationException::withMessages(['name' => 'Published sessions cannot be edited. Close or cancel this session and create another.']);
                }
            }
            $opens = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['opens_at'], config('rintis.timezone'))->startOfMinute()->utc();
            $closes = CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['closes_at'], config('rintis.timezone'))->startOfMinute()->utc();
            $publishing = $data['intent'] === 'publish';
            if ($publishing) {
                if ($closes->lte(now())) {
                    throw ValidationException::withMessages(['closes_at' => 'Closing time must be in the future.']);
                }
                if (! $data['items']) {
                    throw ValidationException::withMessages(['items' => 'Select at least one product before opening a session.']);
                }
                if (! $data['pickup_enabled'] && ! $data['delivery_enabled']) {
                    throw ValidationException::withMessages(['pickup_enabled' => 'Enable pickup or delivery before opening a session.']);
                }
                $other = $store->openOrders()->where(function ($query): void {
                    $query->where(fn ($active) => $active->where('status', 'published')->where('closes_at', '>', now()))
                        ->orWhereHas('orders', fn ($orders) => $orders->whereNotIn('status', ['completed', 'expired', 'cancelled']));
                });
                if ($period) {
                    $other->whereKeyNot($period->id);
                }
                if ($other->exists()) {
                    throw ValidationException::withMessages(['name' => 'Finish the current session and its outstanding orders before publishing another session.']);
                }
                if (($data['pickup_enabled'] && ! $store->pickup_enabled) || ($data['delivery_enabled'] && ! $store->delivery_enabled)) {
                    throw ValidationException::withMessages(['pickup_enabled' => 'Enable these receiving methods in Store settings first.']);
                }
                if (! $store->address_line || ! $store->province || ! $store->city || ! $store->district || $store->latitude === null || $store->longitude === null) {
                    throw ValidationException::withMessages(['pickup_enabled' => 'Complete your store address and map pin in Settings first.']);
                }
                if ($data['delivery_enabled'] && (! $store->delivery_min_distance_m || $store->delivery_max_distance_m < $store->delivery_min_distance_m || ! in_array($store->delivery_fee_mode, ['fixed', 'road_distance'], true) || ($store->delivery_fee_mode === 'fixed' ? $store->delivery_fixed_fee === null : $store->delivery_rate_per_km === null))) {
                    throw ValidationException::withMessages(['delivery_enabled' => 'Complete delivery pricing and distance limits in Store settings.']);
                }
            }
            foreach (['priority', 'hemat'] as $service) {
                $defaults = $store->delivery_options[$service] ?? [];
                foreach (['enabled' => false, 'fee' => 0, 'minutes' => $service === 'priority' ? 30 : 60] as $field => $fallback) {
                    $data[$service.'_'.$field] = $data[$service.'_'.$field] ?? $defaults[$field] ?? $fallback;
                }
            }
            $products = $store->catalogProducts()->whereIn('id', array_column($data['items'], 'catalog_product_id'))->get()->keyBy('id');
            if ($products->count() !== count($data['items'])) {
                throw ValidationException::withMessages(['items' => 'One or more products are unavailable. Review your selection.']);
            }
            $deliveryStarts = ! empty($data['delivery_starts_at']) ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['delivery_starts_at'], config('rintis.timezone'))->utc() : null;
            $deliveryEnds = ! empty($data['delivery_ends_at']) ? CarbonImmutable::createFromFormat('Y-m-d\TH:i', $data['delivery_ends_at'], config('rintis.timezone'))->utc() : null;
            if ($deliveryStarts && ($deliveryStarts->lt($closes) || ! $deliveryEnds || $deliveryEnds->lte($deliveryStarts))) {
                throw ValidationException::withMessages(['delivery_starts_at' => 'Delivery must start after ordering closes and end after it starts.']);
            }
            $attributes = ['name' => $data['name'], 'status' => $publishing ? 'published' : 'draft', 'opens_at' => $opens, 'closes_at' => $closes,
                'published_at' => $publishing ? now() : null,
                'fulfillment' => [...$store->only('address_line', 'province', 'city', 'district', 'latitude', 'longitude', 'pickup_instructions', 'delivery_fee_mode', 'delivery_fixed_fee', 'delivery_rate_per_km', 'delivery_min_distance_m', 'delivery_max_distance_m'),
                    'delivery_fee_mode' => $data['delivery_fee_mode'] ?? $store->delivery_fee_mode, 'delivery_fixed_fee' => $data['delivery_fixed_fee'] ?? $store->delivery_fixed_fee, 'delivery_rate_per_km' => $data['delivery_rate_per_km'] ?? $store->delivery_rate_per_km,
                    'delivery_starts_at' => $data['delivery_enabled'] ? $deliveryStarts?->toIso8601String() : null, 'delivery_ends_at' => $data['delivery_enabled'] ? $deliveryEnds?->toIso8601String() : null,
                    'delivery_options' => ['priority' => ['enabled' => (bool) ($data['priority_enabled'] ?? false), 'fee' => (int) ($data['priority_fee'] ?? 0), 'minutes' => (int) ($data['priority_minutes'] ?? 30)], 'hemat' => ['enabled' => (bool) ($data['hemat_enabled'] ?? false), 'fee' => (int) ($data['hemat_fee'] ?? 0), 'minutes' => (int) ($data['hemat_minutes'] ?? 60)]],
                    'pickup_enabled' => (bool) $data['pickup_enabled'], 'delivery_enabled' => (bool) $data['delivery_enabled']]];
            if ($period) {
                $period->update($attributes);
                $period->items()->delete();
            } else {
                $period = $store->openOrders()->create($attributes);
            }
            foreach ($data['items'] as $item) {
                $product = $products[$item['catalog_product_id']];
                $period->items()->create([...$item, ...$product->only('name', 'description', 'options', 'costing', 'image_path')]);
            }

            return $period;
        });
    }
}
