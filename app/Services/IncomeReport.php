<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;

class IncomeReport
{
    public function received(Order $order): int
    {
        if ($order->payment_status !== 'paid' || in_array($order->status, ['expired', 'cancelled'], true)) {
            return 0;
        }

        return $order->payment_method === 'cash' ? max(0, ($order->cash_received ?? $order->total) - ($order->cash_change ?? 0)) : $order->total;
    }

    public function summarize(Builder $query): array
    {
        $totals = ['productSales' => 0, 'deliverySales' => 0, 'completedOrders' => 0, 'received' => 0, 'cash' => 0, 'qris' => 0, 'bank_transfer' => 0, 'changeReturned' => 0, 'extraCash' => 0, 'paidInProgress' => 0, 'trend' => []];
        foreach ((clone $query)->select(['id', 'created_at', 'status', 'payment_status', 'payment_method', 'subtotal', 'delivery_fee', 'total', 'cash_received', 'cash_change'])->lazyById(500) as $order) {
            if ($order->payment_status !== 'paid' || in_array($order->status, ['expired', 'cancelled'], true)) {
                continue;
            }
            $received = $this->received($order);
            $day = $order->created_at->setTimezone(config('rintis.timezone'))->toDateString();
            $totals['trend'][$day] ??= ['received' => 0, 'sales' => 0];
            $totals['trend'][$day]['received'] += $received;
            $totals['received'] += $received;
            $totals[$order->payment_method] += $received;
            if ($order->payment_method === 'cash') {
                $totals['changeReturned'] += $order->cash_change ?? 0;
                $totals['extraCash'] += max(0, $received - $order->total);
            }
            if ($order->status === 'completed') {
                $totals['trend'][$day]['sales'] += $order->subtotal + $order->delivery_fee;
                $totals['completedOrders']++;
                $totals['productSales'] += $order->subtotal;
                $totals['deliverySales'] += $order->delivery_fee;
            } else {
                $totals['paidInProgress'] += $received;
            }
        }

        return $totals;
    }
}
