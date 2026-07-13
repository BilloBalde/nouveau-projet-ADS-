<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Notification client unique pour toutes les transitions de statut d'une commande.
 * Statuts supportés : approved, rejected, delivering, delivered.
 */
class OrderStatusNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected Order $order,
        protected string $status,
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        $messages = [
            'approved'   => 'Votre commande n°' . $this->order->id . ' a été validée avec succès.',
            'rejected'   => 'Votre commande n°' . $this->order->id . ' a été annulée.',
            'delivering' => 'Votre commande n°' . $this->order->id . ' est en cours de livraison.',
            'delivered'  => 'Votre commande n°' . $this->order->id . ' a été livrée avec succès.',
        ];

        return [
            'order_id' => $this->order->id,
            'status'   => $this->status,
            'message'  => $messages[$this->status] ?? 'Mise à jour de votre commande n°' . $this->order->id . '.',
            'url'      => route('orders.show', $this->order->id, absolute: false),
        ];
    }
}
