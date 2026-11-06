<?php

namespace App\Services;

use App\Models\Store;
use App\Models\WeeklySchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OpenWeeklySchedule
{
    public function handle(WeeklySchedule $schedule): void
    {
        $now = now(config('rintis.timezone'));
        $date = $now->toDateString();
        DB::transaction(function () use ($schedule, $now, $date): void {
            $schedule = WeeklySchedule::whereKey($schedule->id)->lockForUpdate()->firstOrFail();
            $day = $schedule->days[$now->dayOfWeekIso - 1] ?? null;
            if (! $schedule->enabled || $schedule->last_date === $date || $schedule->skip_date === $date || empty($day['enabled']) || $now->format('H:i') < $day['opens'] || $now->format('H:i') >= $day['closes']) {
                return;
            }
            $store = Store::whereKey($schedule->store_id)->firstOrFail();
            $productIds = $day['product_ids'] ?? $schedule->product_ids;
            $products = $store->catalogProducts()->whereIn('id', $productIds)->get();
            if (! $productIds || $products->count() !== count($productIds)) {
                $schedule->update(['last_error' => 'Review the selected menu: a product is no longer available.']);

                return;
            }
            $closes = $now->copy()->setTimeFromTimeString($day['closes']);
            $deliveryStarts = ! empty($day['delivery_starts']) ? $now->copy()->setTimeFromTimeString($day['delivery_starts']) : $closes->copy()->addMinutes(30);
            $deliveryEnds = ! empty($day['delivery_ends']) ? $now->copy()->setTimeFromTimeString($day['delivery_ends']) : $closes->copy()->addHours(2);
            try {
                app(SaveOpenOrder::class)->handle($store, ['name' => $store->name.' · '.$now->format('d M Y'), 'intent' => 'publish', 'delivery_starts_at' => $store->delivery_enabled ? $deliveryStarts->format('Y-m-d\TH:i') : null, 'delivery_ends_at' => $store->delivery_enabled ? $deliveryEnds->format('Y-m-d\TH:i') : null, 'opens_at' => $date.'T'.$day['opens'], 'closes_at' => $date.'T'.$day['closes'], 'pickup_enabled' => $store->pickup_enabled, 'delivery_enabled' => $store->delivery_enabled, 'items' => $products->map(fn ($product) => ['catalog_product_id' => $product->id, 'price' => $product->price, 'quota' => null])->all()]);
                $schedule->update(['last_date' => $date, 'last_error' => null]);
            } catch (ValidationException $exception) {
                $schedule->update(['last_error' => collect($exception->errors())->flatten()->first()]);
            }
        }, 3);
    }
}
