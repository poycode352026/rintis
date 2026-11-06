<?php

namespace App\Models;

use App\Notifications\OrderActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

#[Fillable(['customer_id', 'store_id', 'open_order_id', 'reference', 'idempotency_key', 'fulfillment_history', 'guest_key', 'access_hash', 'access_token', 'customer_name', 'phone', 'method', 'payment_method', 'payment_status', 'payment_details', 'items', 'fulfillment', 'subtotal', 'delivery_fee', 'total'])]
#[Hidden(['pickup_code', 'pickup_code_hash', 'collected_by', 'fulfillment_history', 'guest_key', 'access_hash', 'access_token', 'items', 'fulfillment', 'phone', 'customer_name'])]
class Order extends Model
{
    protected $table = 'rintis_orders';

    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            $order->store->owner->notify(new OrderActivity($order, 'created'));
        });
        static::updated(function (Order $order): void {
            if ($order->wasChanged('payment_status') && $order->payment_status === 'paid' && $order->payment_method !== 'cash') {
                $order->store->owner->notify(new OrderActivity($order, 'paid'));
            }
        });
        static::creating(function (Order $order): void {
            if ($order->method === 'pickup') {
                $order->pickup_code = Str::upper(Str::random(16));
                $order->pickup_code_hash = hash('sha256', $order->pickup_code);
            }
        });
    }

    public function pickupPass(): ?string
    {
        return $this->method === 'pickup' && $this->status === 'ready' ? $this->pickup_code : null;
    }

    protected function casts(): array
    {
        return ['pickup_code' => 'encrypted', 'collected_at' => 'immutable_datetime', 'cash_received' => 'integer', 'cash_change' => 'integer', 'fulfillment_history' => 'array', 'access_token' => 'encrypted', 'payment_details' => 'array', 'items' => 'array', 'fulfillment' => 'array', 'subtotal' => 'integer', 'delivery_fee' => 'integer', 'total' => 'integer'];
    }

    public function openOrder(): BelongsTo
    {
        return $this->belongsTo(OpenOrder::class);
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function nextStatus(): ?string
    {
        return match ($this->status) {
            'received' => 'preparing',
            'preparing' => 'ready',
            'ready' => $this->method === 'delivery' ? 'out_for_delivery' : null,
            default => null,
        };
    }

    public function receipt(): array
    {
        return [...$this->only('reference', 'customer_name', 'phone', 'method', 'payment_method', 'payment_status', 'status', 'collected_at', 'cash_received', 'cash_change', 'subtotal', 'delivery_fee', 'total'),
            'deliveryStarted' => collect($this->fulfillment_history ?? [])->contains(fn ($event) => ($event['status'] ?? null) === 'delivery_started'),
            'progress' => array_map(fn (array $event): array => array_intersect_key($event, array_flip(['status', 'at'])), $this->fulfillment_history ?? [['status' => 'received', 'at' => $this->created_at->toIso8601String()]]),
            'bank_account' => $this->payment_method !== 'cash' ? $this->payment_details : null,
            'items' => array_map(fn (array $item): array => array_intersect_key($item, array_flip(['name', 'quantity', 'unit_price', 'line_total', 'choices', 'open_order_item_id'])), $this->items),
            'pickup' => $this->method === 'pickup' && in_array($this->status, ['ready', 'completed'], true) ? $this->fulfillment : null,
            'delivery' => $this->method === 'delivery' ? $this->fulfillment : null];
    }
}
