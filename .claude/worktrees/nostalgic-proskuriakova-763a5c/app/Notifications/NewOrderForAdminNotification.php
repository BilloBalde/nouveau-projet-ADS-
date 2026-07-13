<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notifie les administrateurs/managers qu'une nouvelle commande e-commerce attend validation.
 */
class NewOrderForAdminNotification extends Notification
{
    use Queueable;

    public function __construct(protected Order $order) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'order_id'      => $this->order->id,
            'customer_name' => $this->order->user?->name ?? 'Client',
            'total_amount'  => $this->order->total_amount,
            'message'       => 'Une nouvelle commande n°' . $this->order->id . ' est en attente de validation.',
            'url'           => route('admin.orders.show', $this->order->id, absolute: false),
        ];
    }
}
