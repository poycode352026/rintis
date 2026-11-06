<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\IncomeRequest;
use App\Models\Order;
use App\Models\Store;
use App\Services\IncomeReport;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class IncomeController extends Controller
{
    public function entry(Request $request): RedirectResponse
    {
        $store = $request->user()->store;

        return $store ? to_route('admin.income.index', $store) : to_route('admin.stores.create');
    }

    public function index(IncomeRequest $request, Store $store, IncomeReport $report): Response
    {
        $data = $request->validated();
        $today = CarbonImmutable::now(config('rintis.timezone'));
        $from = CarbonImmutable::parse($data['from'] ?? $today->startOfMonth()->toDateString(), config('rintis.timezone'))->startOfDay();
        $to = CarbonImmutable::parse($data['to'] ?? $today->toDateString(), config('rintis.timezone'))->endOfDay();
        if ($from->diffInDays($to) > 366) {
            throw ValidationException::withMessages(['to' => 'Choose a date range of up to one year.']);
        }
        $payment = $data['payment'] ?? 'all';
        $query = $store->orders()->getQuery()->whereBetween('created_at', [$from->utc(), $to->utc()])->when($payment !== 'all', fn ($query) => $query->where('payment_method', $payment));

        return Inertia::render('Admin/Income', [
            'storeName' => $store->name,
            'catalogUrl' => route('admin.catalog.index', $store), 'settingsUrl' => route('admin.stores.edit', $store),
            'filterUrl' => route('admin.income.index', $store),
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'payment' => $payment],
            'presets' => ['today' => $today->toDateString(), 'week' => $today->startOfWeek()->toDateString(), 'month' => $today->startOfMonth()->toDateString()],
            'summary' => $report->summarize(clone $query),
            'orders' => $query->select(['id', 'store_id', 'open_order_id', 'reference', 'customer_name', 'created_at', 'payment_method', 'payment_status', 'status', 'subtotal', 'delivery_fee', 'total', 'cash_received', 'cash_change'])->latest('id')->paginate(20)->withQueryString()->through(fn (Order $order) => [
                ...$order->only('id', 'reference', 'customer_name', 'payment_method', 'payment_status', 'status', 'subtotal', 'delivery_fee', 'total', 'cash_received', 'cash_change'),
                'placedAt' => $order->created_at->toIso8601String(), 'received' => $report->received($order),
                'url' => route('admin.open-orders.orders', [$store, $order->open_order_id, 'view' => 'all', 'focus' => $order->id]),
            ]),
        ]);
    }
}
