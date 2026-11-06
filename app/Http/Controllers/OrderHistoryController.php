<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ExpireQrisOrder;
use App\Services\GuestOrderIdentity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class OrderHistoryController extends Controller
{
    public function __invoke(Request $request, GuestOrderIdentity $identity): Response
    {
        $guestKey = $identity->key($request);
        $orders = Order::query()->where(function (Builder $query) use ($request, $guestKey): void {
            $query->where(fn (Builder $guest) => $guest->where('guest_key', $guestKey)->whereNull('customer_id'));
            if ($request->user()) {
                $query->orWhere('customer_id', $request->user()->id);
            }
        })->with('store:id,name,slug')->latest('id')->paginate(12)->through(function (Order $order): array {
            app(ExpireQrisOrder::class)->handle($order);
            $order->refresh();

            return [
                ...$order->only('reference', 'status', 'payment_status', 'payment_method', 'method', 'total'),
                'placedAt' => $order->created_at->toIso8601String(), 'storeName' => $order->store->name,
                'items' => array_map(fn (array $item): array => array_intersect_key($item, array_flip(['name', 'quantity'])), $order->items),
                'url' => route('order.receipt', $order->access_token),
            ];
        });
        $response = Inertia::render('Storefront/OrderHistory', ['orders' => $orders, 'signedIn' => $request->user() !== null, 'directoryUrl' => route('directory')])->toResponse($request);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $identity->remember($request, $response);
    }
}
