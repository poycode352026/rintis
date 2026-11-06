<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\MidtransPayment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MidtransController extends Controller
{
    public function retry(string $token, MidtransPayment $payments): RedirectResponse
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/', $token), 404);
        $order = Order::query()->where('access_hash', hash('sha256', $token))->firstOrFail();
        abort_unless(($order->payment_details['provider'] ?? null) === 'midtrans', 404);
        $payments->sync($order, create: true);

        return to_route('order.receipt', $token);
    }

    public function refresh(string $token, MidtransPayment $payments): RedirectResponse
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/', $token), 404);
        $order = Order::query()->where('access_hash', hash('sha256', $token))->firstOrFail();
        $payments->sync($order, force: true);

        return to_route('order.receipt', $token);
    }

    public function webhook(Request $request, MidtransPayment $payments): JsonResponse
    {
        $data = $request->validate(['order_id' => ['required', 'string', 'max:100']]);
        $order = Order::query()->where('payment_details->provider', 'midtrans')->where('payment_details->provider_order_id', $data['order_id'])->firstOrFail();
        // Ignore claimed status/signature: query Midtrans with our own secret credentials.
        abort_unless($payments->sync($order, force: true), 503);

        return response()->json(['received' => true]);
    }
}
