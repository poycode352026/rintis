<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\CatalogProduct;
use App\Models\Order;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    public function __invoke(ReviewRequest $request, string $token): RedirectResponse
    {
        abort_unless(preg_match('/^[a-zA-Z0-9]{64}$/', $token), 404);
        DB::transaction(function () use ($request, $token): void {
            $order = Order::query()->where('access_hash', hash('sha256', $token))->lockForUpdate()->firstOrFail();
            abort_unless($order->status === 'completed' && $order->payment_status === 'paid', 403);
            $data = $request->validated();
            $reviews = $data['reviews'] ?? [['line_index' => $data['line_index'], 'rating' => $data['rating']]];
            $menu = $order->openOrder->items()->get()->keyBy('id');
            $products = CatalogProduct::withTrashed()->where('store_id', $order->store_id)->whereIn('id', $menu->pluck('catalog_product_id'))->pluck('id')->all();
            foreach ($reviews as $review) {
                $index = (int) $review['line_index'];
                $line = $order->items[$index] ?? null;
                abort_unless($line, 404);
                $productId = $menu->get($line['open_order_item_id'] ?? null)?->catalog_product_id;
                $index = collect($order->items)->search(fn (array $item): bool => ($item['open_order_item_id'] ?? null) === ($line['open_order_item_id'] ?? null));
                $existing = ProductReview::query()->where('order_id', $order->id)->where('catalog_product_id', $productId)->exists();
                if ($existing) {
                    continue;
                }
                ProductReview::query()->firstOrCreate(['order_id' => $order->id, 'line_index' => $index], ['catalog_product_id' => in_array($productId, $products, true) ? $productId : null, 'rating' => $review['rating']]);
            }
        }, 3);

        return to_route('order.receipt', $token)->with('success', 'Thank you for your rating.');
    }
}
