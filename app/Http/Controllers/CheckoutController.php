<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckoutRequest;
use App\Models\OpenOrder;
use App\Models\Order;
use App\Models\ProductReview;
use App\Models\Store;
use App\Services\ActiveCheckout;
use App\Services\ExpireQrisOrder;
use App\Services\GuestOrderIdentity;
use App\Services\MidtransPayment;
use App\Services\PlaceOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class CheckoutController extends Controller
{
    public function show(Request $request, Store $store, OpenOrder $openOrder, GuestOrderIdentity $identity, ActiveCheckout $activeCheckout): Response
    {
        abort_unless($store->is_listed && $openOrder->status === 'published', 404);

        if ($active = $activeCheckout->find($identity->key($request), $request->user()?->id)) {
            return $identity->remember($request, to_route('order.receipt', $active->access_token));
        }

        $guestKey = $identity->key($request);
        $resetCheckoutKey = $openOrder->orders()->whereIn('status', ['expired', 'cancelled'])->where(function ($query) use ($request, $guestKey): void {
            $query->where(fn ($guest) => $guest->where('guest_key', $guestKey)->whereNull('customer_id'));
            if ($request->user()) {
                $query->orWhere('customer_id', $request->user()->id);
            }
        })->exists();
        $response = Inertia::render('Storefront/Checkout', [
            'gatewayAvailable' => app(MidtransPayment::class)->available($store),
            'routingDemo' => (bool) config('rintis.routing_demo'), 'store' => $store->publicProfile(), 'period' => $openOrder->publicSummary(),
            'serverNow' => now()->toIso8601String(), 'paymentDeadline' => $openOrder->closes_at->subMinutes(15)->toIso8601String(),
            'paymentMethods' => ['qris' => $store->bank_transfer_enabled && app(MidtransPayment::class)->available($store) && $openOrder->closes_at->subMinutes(15)->gt(now()), 'cash' => $store->cash_enabled, 'bankTransfer' => false],
            'bankAccounts' => $store->bank_transfer_enabled ? $store->bankAccounts()->where('is_active', true)->orderBy('id')->get(['id', 'bank_name', 'account_holder']) : [],
            'items' => $openOrder->items()->get()->map(fn ($item) => $item->publicDetails()),
            'submitUrl' => route('checkout.store', [$store, $openOrder]), 'quoteUrl' => route('checkout.quote', [$store, $openOrder]),
            'backUrl' => route('open-order', $store), 'resetCheckoutKey' => $resetCheckoutKey, 'checkoutKey' => (string) Str::uuid(), 'mapTilesUrl' => config('rintis.map_tiles_url'),
        ])->toResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $identity->remember($request, $response);
    }

    public function store(CheckoutRequest $request, Store $store, OpenOrder $openOrder, PlaceOrder $place, GuestOrderIdentity $identity): Response
    {
        $data = $request->validated();
        $quote = $request->session()->get('rintis.quotes.'.($data['quote_id'] ?? 'none'));
        $guestKey = $identity->key($request);
        $order = $place->handle($openOrder, $data, $guestKey, $quote, $request->user()?->id);

        app(MidtransPayment::class)->sync($order, create: true);

        return $identity->remember($request, to_route('order.receipt', ['token' => $order->access_token]));
    }

    public function receipt(Request $request, string $token): Response
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/', $token), 404);
        $order = Order::query()->where('access_hash', hash('sha256', $token))->firstOrFail();
        app(MidtransPayment::class)->sync($order);
        app(ExpireQrisOrder::class)->handle($order->fresh());
        $order->refresh();
        $sellerOrder = $request->user()?->id === $order->store->owner_id && collect($order->fulfillment_history ?? [])->contains(fn ($event) => ($event['source'] ?? null) === 'walk_in');
        $response = Inertia::render('Storefront/Receipt', ['order' => [...$order->receipt(), 'pickupPass' => $order->pickupPass(), 'reviews' => $order->status === 'completed' ? ProductReview::query()->where('order_id', $order->id)->get(['line_index', 'rating']) : [], 'reviewUrl' => $order->status === 'completed' && $order->payment_status === 'paid' ? route('order.review', $token) : null, 'paymentRefreshUrl' => route('order.payment.refresh', $token), 'paymentRetryUrl' => ($order->payment_details['provider'] ?? null) === 'midtrans' ? route('order.payment.retry', $token) : null], 'serverNow' => now()->toIso8601String(), 'storeName' => $order->store->name, 'mapTilesUrl' => config('rintis.map_tiles_url'), 'backLabel' => $sellerOrder ? 'Back to orders' : 'Back to menu', 'backUrl' => $sellerOrder ? route('admin.open-orders.orders', [$order->store, $order->openOrder]) : route('open-order', $order->store)])
            ->toResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }

    public function quote(Request $request, Store $store, OpenOrder $openOrder): JsonResponse
    {
        abort_unless($store->is_listed && $openOrder->status === 'published' && $openOrder->opens_at->lte(now()) && $openOrder->closes_at->gt(now()), 422);
        $data = $request->validate(['latitude' => ['required', 'numeric', 'between:-90,90'], 'longitude' => ['required', 'numeric', 'between:-180,180']]);
        $settings = $openOrder->fulfillment;
        if (empty($settings['delivery_enabled'])) {
            throw ValidationException::withMessages(['delivery' => 'Delivery is unavailable.']);
        }
        if (! config('rintis.routing_url')) {
            throw ValidationException::withMessages(['delivery' => 'Delivery routing is not configured yet. Choose pickup if available.']);
        }
        if (config('rintis.routing_demo')) {
            if (! app()->environment(['local', 'testing'])) {
                throw ValidationException::withMessages(['delivery' => 'Delivery routing needs a production provider.']);
            }
            if (RateLimiter::hit('rintis-demo-routing', 1) > 1) {
                throw ValidationException::withMessages(['delivery' => 'Please wait one second before calculating another route.']);
            }
        }
        try {
            $coordinates = $settings['longitude'].','.$settings['latitude'].';'.$data['longitude'].','.$data['latitude'];
            $response = Http::acceptJson()->withUserAgent('OneclickStore-Rintis/1.0 ('.config('app.url').')')->connectTimeout(4)->timeout(10)->get(rtrim(config('rintis.routing_url'), '/').'/'.$coordinates, ['overview' => 'false', 'radiuses' => '100;100'])->throw();
            $distance = $response->json('routes.0.distance');
            if ($response->json('code') !== 'Ok' || ! is_numeric($distance) || $distance < 0) {
                throw new \RuntimeException;
            }
        } catch (Throwable) {
            throw ValidationException::withMessages(['delivery' => 'A road route could not be calculated. Retry or choose pickup.']);
        }
        if ($distance < $settings['delivery_min_distance_m'] || $distance > $settings['delivery_max_distance_m']) {
            throw ValidationException::withMessages(['delivery' => 'This location is outside the delivery distance range.']);
        }
        $fee = $settings['delivery_fee_mode'] === 'fixed' ? (int) $settings['delivery_fixed_fee'] : (int) round($distance * $settings['delivery_rate_per_km'] / 1000);
        $id = (string) Str::uuid();
        $quote = ['period_id' => $openOrder->id, 'latitude' => (string) $data['latitude'], 'longitude' => (string) $data['longitude'], 'fee' => $fee, 'distance_m' => (int) round($distance), 'expires' => now()->addMinutes(15)->timestamp];
        $request->session()->put('rintis.quotes', [$id => $quote]);

        return response()->json(['id' => $id, 'fee' => $fee, 'distance_m' => $quote['distance_m'], 'expires' => $quote['expires']]);
    }
}
