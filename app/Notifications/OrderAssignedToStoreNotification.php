<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Prévient les employés d'une boutique qu'une commande en ligne leur a été
 * attribuée : à eux de la préparer et de la livrer.
 */
class OrderAssignedToStoreNotification extends Notification
{
    use Queueable;

    public function __construct(protected Order $order, protected Store $store) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->order->id,
            'customer_name' => $this->order->user?->name ?? 'Client',
            'total_amount' => $this->order->total_amount,
            'message' => 'La commande n°'.$this->order->id.' a été attribuée à '.$this->store->store_name.' : à préparer et livrer.',
            'url' => route('admin.orders.show', $this->order->id, absolute: false),
        ];
    }
}
