<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Prévient le siège qu'une boutique lui a rendu une commande en ligne.
 */
class OrderReturnedToHeadOfficeNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Order $order,
        protected string $boutique,
        protected string $par,
        protected string $motif = ''
    ) {}

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
            'message'       => sprintf(
                'La commande n°%d a été rendue au siège par %s (%s)%s.',
                $this->order->id,
                $this->boutique,
                $this->par,
                $this->motif !== '' ? ' : ' . $this->motif : ''
            ),
            'url'           => route('admin.orders.show', $this->order->id, absolute: false),
        ];
    }
}
