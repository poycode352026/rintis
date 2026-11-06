<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Notifications\Notification;

class OrderActivity extends Notification
{
    public function __construct(private Order $order, private string $kind) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['store_id' => $this->order->store_id, 'order_id' => $this->order->id, 'reference' => $this->order->reference,
            'kind' => $this->kind, 'title' => $this->kind === 'paid' ? 'Payment received' : 'New order', 'total' => $this->order->total];
    }
}
