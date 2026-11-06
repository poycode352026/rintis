<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\ExpireQrisOrder;
use Illuminate\Console\Command;

class ExpireQrisOrders extends Command
{
    protected $signature = 'rintis:expire-qris';

    protected $description = 'Expire unpaid QRIS orders at the session payment cutoff';

    public function handle(ExpireQrisOrder $expire): int
    {
        $processed = 0;
        $until = microtime(true) + 50;
        Order::query()->where('payment_method', 'qris')->where('payment_status', '!=', 'paid')->where('status', 'received')
            ->whereHas('openOrder', fn ($query) => $query->where('closes_at', '<=', now()->addMinutes(15)))
            ->eachById(function (Order $order) use ($expire, &$processed, $until) {
                if (microtime(true) >= $until) {
                    return false;
                }
                if ($expire->handle($order)) {
                    $processed++;
                }
            }, 50);
        $this->info($processed.' QRIS orders expired.');

        return self::SUCCESS;
    }
}
