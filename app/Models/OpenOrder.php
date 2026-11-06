<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'status', 'opens_at', 'closes_at', 'fulfillment', 'published_at', 'closed_at'])]
#[Hidden(['fulfillment'])]
class OpenOrder extends Model
{
    protected $table = 'rintis_open_orders';

    protected function casts(): array
    {
        return ['opens_at' => 'immutable_datetime', 'closes_at' => 'immutable_datetime', 'published_at' => 'immutable_datetime', 'closed_at' => 'immutable_datetime', 'fulfillment' => 'array'];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OpenOrderItem::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function displayStatus(): string
    {
        if ($this->status !== 'published') {
            return $this->status;
        }
        if ($this->closes_at->lte(now())) {
            return 'ended';
        }

        return $this->opens_at->gt(now()) ? 'scheduled' : 'open';
    }

    public function deliveryOptions(): array
    {
        if (empty($this->fulfillment['delivery_enabled'])) {
            return [];
        }
        $options = [['key' => 'standard', 'name' => 'Standard', 'feeMode' => 'road', 'fee' => 0, 'startsAt' => $this->fulfillment['delivery_starts_at'] ?? null, 'endsAt' => $this->fulfillment['delivery_ends_at'] ?? null, 'minutes' => null]];
        foreach (['priority' => 'Priority', 'hemat' => 'Hemat'] as $key => $name) {
            $setting = $this->fulfillment['delivery_options'][$key] ?? [];
            if (empty($setting['enabled'])) {
                continue;
            }
            $start = $key === 'hemat' ? $this->closes_at->addMinutes(15) : now()->toImmutable();
            $options[] = ['key' => $key, 'name' => $name, 'feeMode' => $key === 'hemat' ? 'fixed' : 'extra', 'fee' => (int) $setting['fee'], 'minutes' => (int) $setting['minutes'], 'startsAt' => $start->toIso8601String(), 'endsAt' => $start->addMinutes((int) $setting['minutes'])->toIso8601String()];
        }

        return $options;
    }

    public function publicSummary(): array
    {
        return [...$this->only('id', 'name'), 'status' => $this->displayStatus(),
            'deliveryOptions' => $this->deliveryOptions(),
            'deliveryWindow' => ! empty($this->fulfillment['delivery_starts_at']) && ! empty($this->fulfillment['delivery_ends_at']) ? ['startsAt' => $this->fulfillment['delivery_starts_at'], 'endsAt' => $this->fulfillment['delivery_ends_at']] : null,
            'opensAt' => $this->opens_at->toIso8601String(), 'closesAt' => $this->closes_at->toIso8601String(),
            'pickup' => (bool) $this->fulfillment['pickup_enabled'], 'delivery' => (bool) $this->fulfillment['delivery_enabled']];
    }
}
