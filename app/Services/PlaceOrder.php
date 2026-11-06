<?php

namespace App\Services;

use App\Models\OpenOrder;
use App\Models\Order;
use App\Models\Store;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PlaceOrder
{
    public function __construct(private ActiveCheckout $activeCheckout) {}

    public function handle(OpenOrder $period, array $data, string $guestKey, ?array $quote, ?int $customerId = null): Order
    {
        try {
            return DB::transaction(function () use ($period, $data, $guestKey, $quote, $customerId): Order {
                $this->activeCheckout->lock($guestKey, $customerId);
                $store = Store::query()->whereKey($period->store_id)->lockForUpdate()->firstOrFail();
                $period = OpenOrder::query()->whereKey($period->id)->lockForUpdate()->firstOrFail();
                $existing = Order::query()->where('idempotency_key', $data['idempotency_key'])->first();
                if ($existing) {
                    if ($existing->guest_key !== $guestKey || $existing->open_order_id !== $period->id || ($existing->customer_id !== null && $existing->customer_id !== $customerId)) {
                        throw ValidationException::withMessages(['items' => 'This checkout key cannot be reused. Reload checkout.']);
                    }

                    return $existing;
                }
                if ($this->activeCheckout->find($guestKey, $customerId)) {
                    throw ValidationException::withMessages(['items' => 'Finish your current order before starting another checkout. Reopen checkout to resume your active order.']);
                }
                if ($period->status !== 'published' || $period->opens_at->gt(now()) || $period->closes_at->lte(now())) {
                    throw ValidationException::withMessages(['items' => 'This open-order session is not accepting orders.']);
                }
                if ($data['payment_method'] === 'qris' && $period->closes_at->subMinutes(15)->lte(now())) {
                    throw ValidationException::withMessages(['payment_method' => 'Digital payment closed 15 minutes before this session ends.']);
                }
                $paymentEnabled = $data['payment_method'] === 'cash' ? $store->cash_enabled : $store->bank_transfer_enabled;
                if (! $paymentEnabled) {
                    throw ValidationException::withMessages(['payment_method' => 'This payment method is no longer enabled by the seller. Please choose another method.']);
                }
                if (empty($period->fulfillment[$data['method'].'_enabled'])) {
                    throw ValidationException::withMessages(['method' => 'This receiving method is not available.']);
                }
                $menu = $period->items()->orderBy('id')->lockForUpdate()->get()->keyBy('id');
                $quantities = [];
                $lines = [];
                $subtotal = 0;
                foreach ($data['items'] as $index => $line) {
                    $item = $menu->get($line['id']);
                    if (! $item) {
                        throw ValidationException::withMessages(['items' => 'One of the selected products is not in this session.']);
                    }
                    $selected = $line['choices'];
                    $matched = [];
                    $labels = [];
                    $extra = 0;
                    foreach ($item->options ?? [] as $group) {
                        $choices = array_values(array_filter($group['choices'], fn ($choice) => in_array($choice['id'], $selected, true)));
                        if (($group['required'] && ! $choices) || ($group['selection'] === 'single' && count($choices) > 1)) {
                            throw ValidationException::withMessages(['items' => 'Review the choices for '.$item->name.'.']);
                        }
                        foreach ($choices as $choice) {
                            $matched[] = $choice['id'];
                            $extra += (int) $choice['price_adjustment'];
                            $labels[] = ['group' => $group['name'], 'name' => $choice['name'], 'price_adjustment' => (int) $choice['price_adjustment']];
                        }
                    }
                    if (count($matched) !== count($selected)) {
                        throw ValidationException::withMessages(['items' => 'A selected product choice is no longer valid.']);
                    }
                    $unit = $item->price + $extra;
                    $total = $unit * $line['quantity'];
                    $subtotal += $total;
                    $quantities[$item->id] = ($quantities[$item->id] ?? 0) + $line['quantity'];
                    $lines[] = ['name' => $item->name, 'quantity' => $line['quantity'], 'unit_price' => $unit, 'line_total' => $total, 'choices' => $labels,
                        'open_order_item_id' => $item->id, 'private_costing' => $item->costing];
                }
                foreach ($quantities as $id => $quantity) {
                    $item = $menu[$id];
                    if ($item->quota !== null && $item->ordered_quantity + $quantity > $item->quota) {
                        throw ValidationException::withMessages(['items' => $item->name.' does not have enough quota. Please update your cart.']);
                    }
                }
                $fee = 0;
                $service = null;
                if ($data['method'] === 'delivery') {
                    if (! $quote || $quote['period_id'] !== $period->id || $quote['expires'] < now()->timestamp
                        || $quote['latitude'] !== (string) $data['latitude'] || $quote['longitude'] !== (string) $data['longitude']) {
                        throw ValidationException::withMessages(['quote_id' => 'Calculate delivery again; the quote is missing, changed or expired.']);
                    }
                    $service = collect($period->deliveryOptions())->firstWhere('key', $data['delivery_service'] ?? 'standard');
                    if (! $service) {
                        throw ValidationException::withMessages(['delivery_service' => 'This delivery option is unavailable. Choose another option.']);
                    }
                    $fee = $service['feeMode'] === 'fixed' ? $service['fee'] : $quote['fee'] + $service['fee'];
                }
                $bank = null;
                if ($data['payment_method'] === 'qris') {
                    if (! app(MidtransPayment::class)->available($store)) {
                        throw ValidationException::withMessages(['payment_method' => 'QRIS is unavailable for this store.']);
                    }
                    $bank = ['provider' => 'midtrans', 'payment_type' => 'qris', 'provider_order_id' => 'RINTIS-'.str_replace('-', '', $data['idempotency_key']), 'qr_url' => null, 'status' => 'creating', 'expires_at' => $period->closes_at->subMinutes(15)->toIso8601String()];
                } elseif ($data['payment_method'] === 'bank_transfer' && ! empty($data['payment_bank'])) {
                    if (! app(MidtransPayment::class)->available($store)) {
                        throw ValidationException::withMessages(['payment_method' => 'Online payment is unavailable for this store.']);
                    }
                    $bank = ['provider' => 'midtrans', 'provider_order_id' => 'RINTIS-'.str_replace('-', '', $data['idempotency_key']), 'bank' => $data['payment_bank'], 'bank_name' => strtoupper($data['payment_bank']).' Virtual Account', 'account_holder' => $store->name, 'account_number' => null, 'status' => 'creating', 'expires_at' => $period->closes_at->subMinutes(15)->toIso8601String()];
                } elseif ($data['payment_method'] === 'bank_transfer') {
                    $account = $period->store->bankAccounts()->whereKey($data['bank_account_id'] ?? null)->where('is_active', true)->lockForUpdate()->first();
                    if (! $account) {
                        throw ValidationException::withMessages(['bank_account_id' => 'Choose an active bank account for this store.']);
                    }
                    $bank = $account->only('bank_name', 'account_holder', 'account_number');
                }
                $token = Str::random(64);
                $order = Order::create([
                    'customer_id' => $customerId, 'store_id' => $period->store_id, 'open_order_id' => $period->id, 'reference' => 'RT-'.Str::upper(Str::random(12)),
                    'idempotency_key' => $data['idempotency_key'], 'guest_key' => $guestKey, 'access_token' => $token, 'access_hash' => hash('sha256', $token),
                    'customer_name' => $data['customer_name'], 'phone' => $data['phone'], 'method' => $data['method'], 'payment_method' => $data['payment_method'], 'payment_status' => 'unpaid', 'payment_details' => $bank,
                    'items' => $lines, 'subtotal' => $subtotal, 'delivery_fee' => $fee, 'total' => $subtotal + $fee,
                    'fulfillment' => $data['method'] === 'pickup' ? array_intersect_key($period->fulfillment, array_flip(['address_line', 'province', 'city', 'district', 'latitude', 'longitude', 'pickup_instructions']))
                        : ['delivery_service' => $service['key'], 'delivery_service_name' => $service['name'], 'delivery_starts_at' => $service['startsAt'], 'delivery_ends_at' => $service['endsAt'], 'address_line' => $data['delivery_address'], 'latitude' => $data['latitude'], 'longitude' => $data['longitude'], 'distance_m' => $quote['distance_m']],
                ]);
                foreach ($quantities as $id => $quantity) {
                    $menu[$id]->increment('ordered_quantity', $quantity);
                }

                return $order;
            }, 3);
        } catch (UniqueConstraintViolationException $exception) {
            $existing = Order::query()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing && $existing->guest_key === $guestKey && $existing->open_order_id === $period->id && ($existing->customer_id === null || $existing->customer_id === $customerId)) {
                return $existing;
            }
            if ($existing) {
                throw ValidationException::withMessages(['items' => 'This checkout key cannot be reused. Reload checkout.']);
            }
            throw $exception;
        }
    }
}
